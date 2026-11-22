package pl.mpc.sps;

import org.bukkit.Bukkit;
import org.bukkit.command.Command;
import org.bukkit.command.CommandSender;
import org.bukkit.entity.Player;
import org.bukkit.event.EventHandler;
import org.bukkit.event.Listener;
import org.bukkit.event.player.PlayerJoinEvent;
import org.bukkit.configuration.file.YamlConfiguration;
import org.bukkit.plugin.java.JavaPlugin;
import java.io.*;
import java.net.*;
import java.nio.charset.StandardCharsets;
import java.nio.file.*;
import java.security.MessageDigest;
import java.util.*;

/** Bukkit/Paper bridge. Remote input never becomes a console command. */
public final class ServerRewards extends JavaPlugin implements Listener {
    private File journalFile;
    private YamlConfiguration journal;
    private final Set<UUID> inFlight = new HashSet<>();
    private final Map<UUID,Long> attempts = new HashMap<>();
    private String endpoint, key, reward;
    private int serverId;

    @Override public void onEnable() {
        saveDefaultConfig();
        endpoint=getConfig().getString("endpoint", "");
        key=getConfig().getString("api-key", "");
        reward=getConfig().getString("reward-command", "give %player% minecraft:diamond 1");
        serverId=getConfig().getInt("server-id");
        try {
            URI uri=new URI(endpoint);
            if (!"https".equals(uri.getScheme()) || uri.getHost()==null || uri.getUserInfo()!=null || uri.getFragment()!=null
                || !key.matches("[a-fA-F0-9]{64}") || serverId<1 || reward.trim().isEmpty() || reward.contains("\n") || reward.contains("\r")) {
                throw new IllegalArgumentException("Nieprawidlowa konfiguracja");
            }
        } catch (Exception e) {
            getLogger().severe("Ustaw endpoint HTTPS, server-id, api-key oraz reward-command w config.yml.");
            Bukkit.getPluginManager().disablePlugin(this); return;
        }
        if (!Bukkit.getOnlineMode() && !getConfig().getBoolean("trusted-proxy",false)) {
            getLogger().severe("Nagrody wymagaja online-mode lub poprawnie zabezpieczonego proxy (trusted-proxy).");
            Bukkit.getPluginManager().disablePlugin(this); return;
        }
        journalFile=new File(getDataFolder(),"claims.yml");
        journal=new YamlConfiguration();
        try { if (journalFile.exists()) journal.load(journalFile); }
        catch (Exception e) { getLogger().severe("Nie mozna odczytac claims.yml. Zablokowano nagrody."); Bukkit.getPluginManager().disablePlugin(this); return; }
        Bukkit.getPluginManager().registerEvents(this,this);
        for (String id:journal.getKeys(false)) {
            String state=journal.getString(id+".state","");
            if (state.equals("requesting") || state.equals("executing") || state.equals("uncertain")) {
                getLogger().warning("Reczne sprawdzenie nagrody: "+id+" / "+state+" / "+journal.getString(id+".player"));
            }
        }
        for (Player player:Bukkit.getOnlinePlayers()) deliverQueued(player);
    }

    @Override public boolean onCommand(CommandSender sender,Command command,String label,String[] args) {
        if (!(sender instanceof Player)) { sender.sendMessage("Komenda tylko dla gracza."); return true; }
        Player player=(Player)sender;
        if (args.length!=1 || !args[0].matches("[a-fA-F0-9]{24}")) { player.sendMessage("Uzycie: /nagroda KOD_Z_FORUM"); return true; }
        if (!player.getName().matches("[A-Za-z0-9_]{3,16}")) { player.sendMessage("Nieobslugiwany nick gracza."); return true; }
        UUID uuid=player.getUniqueId(); long now=System.currentTimeMillis();
        if (inFlight.contains(uuid) || now-attempts.getOrDefault(uuid,0L)<10000) { player.sendMessage("Poczekaj przed kolejna proba."); return true; }
        String code=args[0].toUpperCase(Locale.ROOT), id=hash(serverId+":"+code);
        if (journal.contains(id)) { player.sendMessage("Ten kod byl juz sprawdzany. W razie problemu skontaktuj sie z administracja."); return true; }
        attempts.put(uuid,now);
        journal.set(id+".player",player.getName()); journal.set(id+".uuid",uuid.toString());
        journal.set(id+".created",now); journal.set(id+".state","requesting");
        if (!persist()) { player.sendMessage("Blad zapisu. Nagrody chwilowo niedostepne."); return true; }
        inFlight.add(uuid); player.sendMessage("Sprawdzam kod...");
        final String name=player.getName();
        Bukkit.getScheduler().runTaskAsynchronously(this,()->{
            int result=claim(code,name);
            if (!isEnabled()) return; // requesting remains in the journal for manual reconciliation.
            Bukkit.getScheduler().runTask(this,()->{
                inFlight.remove(uuid);
                journal.set(id+".state",result==1?"ready":result==0?"rejected":"uncertain");
                if (!persist()) return;
                Player current=Bukkit.getPlayer(uuid);
                if (result==1) { if (current!=null) deliver(id,current); }
                else if (current!=null) current.sendMessage(result==0?"Kod niepoprawny, zuzyty, wygasly albo przypisany do innego nicku/serwera.":"Brak potwierdzenia z forum. Administracja musi sprawdzic nagrode.");
                if (result<0) getLogger().warning("Niepewny wynik odbioru, sprawdz recznie: "+id+" / "+name);
            });
        });
        return true;
    }
    private int claim(String code,String player) {
        HttpURLConnection connection=null;
        try {
            connection=(HttpURLConnection)new URL(endpoint).openConnection();
            connection.setInstanceFollowRedirects(false); connection.setConnectTimeout(5000); connection.setReadTimeout(8000);
            connection.setRequestMethod("POST"); connection.setDoOutput(true);
            connection.setRequestProperty("X-SPS-Key",key);
            connection.setRequestProperty("Content-Type","application/x-www-form-urlencoded; charset=UTF-8");
            byte[] body=("server="+serverId+"&code="+URLEncoder.encode(code,"UTF-8")+"&player="+URLEncoder.encode(player,"UTF-8")).getBytes(StandardCharsets.UTF_8);
            connection.setFixedLengthStreamingMode(body.length);
            try(OutputStream output=connection.getOutputStream()) { output.write(body); }
            int status=connection.getResponseCode();
            if (status==403) return 0;
            if (status!=200) return -1;
            ByteArrayOutputStream out=new ByteArrayOutputStream();
            try(InputStream input=connection.getInputStream()) {
                byte[] buffer=new byte[256]; int n;
                while ((n=input.read(buffer))!=-1) { if(out.size()+n>2048) return -1; out.write(buffer,0,n); }
            }
            return out.toString("UTF-8").matches("(?s)\\s*\\{\\s*\"ok\"\\s*:\\s*true\\s*\\}\\s*")?1:-1;
        } catch(Exception e) { return -1; }
        finally { if(connection!=null) connection.disconnect(); }
    }
    @EventHandler public void onJoin(PlayerJoinEvent event) {
        Bukkit.getScheduler().runTask(this,()->deliverQueued(event.getPlayer()));
    }
    private void deliverQueued(Player player) {
        for (String id:journal.getKeys(false)) {
            if ("ready".equals(journal.getString(id+".state")) && player.getUniqueId().toString().equals(journal.getString(id+".uuid"))) deliver(id,player);
        }
    }
    private void deliver(String id,Player player) {
        if (!player.isOnline()) return;
        journal.set(id+".state","executing");
        if (!persist()) return;
        try {
            boolean ok=Bukkit.dispatchCommand(Bukkit.getConsoleSender(),reward.replace("%player%",player.getName()));
            journal.set(id+".state",ok?"delivered":"uncertain"); persist();
            player.sendMessage(ok?"Dziekujemy za podbicie! Nagroda zostala przekazana.":"Komenda nagrody nie powiodla sie. Zglos to administracji.");
        } catch (Exception e) {
            journal.set(id+".state","uncertain"); persist();
            getLogger().warning("Sprawdz wykonanie nagrody: "+id);
        }
    }
    private boolean persist() {
        try {
            Path destination=journalFile.toPath(), temporary=new File(getDataFolder(),"claims.yml.tmp").toPath();
            byte[] data=journal.saveToString().getBytes(StandardCharsets.UTF_8);
            try(FileOutputStream stream=new FileOutputStream(temporary.toFile())) { stream.write(data); stream.getFD().sync(); }
            Files.move(temporary,destination,StandardCopyOption.REPLACE_EXISTING,StandardCopyOption.ATOMIC_MOVE);
            return true;
        } catch(Exception e) {
            getLogger().severe("Nie mozna zapisac dziennika nagrod. Plugin wylaczony; wymagane sprawdzenie claims.yml.");
            Bukkit.getPluginManager().disablePlugin(this); return false;
        }
    }
    private static String hash(String value) {
        try {
            byte[] bytes=MessageDigest.getInstance("SHA-256").digest(value.getBytes(StandardCharsets.UTF_8));
            StringBuilder result=new StringBuilder(); for(byte b:bytes) result.append(String.format("%02x",b&255)); return result.toString();
        } catch(Exception e) { throw new IllegalStateException(e); }
    }
}
