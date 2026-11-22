<?php
namespace IPS\mpcservers\Modules\Core;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class FeatureHooks
{
    public static function definitions(): array
    {
        return [
            'topic_views'=>'Statystyki wejść do tematów',
            'link_conversion'=>'Konwersja znaczników spisu w zapisanych postach',
            'archive'=>'Automatyczna archiwizacja publikacji',
            'awards'=>'Rozliczanie zakończonych okresów TOP',
            'status'=>'Sprawdzanie dostępności serwerów',
            'cleanup'=>'Sprzątanie cache proxy i wygasłych danych',
            'loader'=>'Podglądy, SEO i zastępowanie treści tematów-spisów',
            'badges'=>'Prefiksy tematów i list działów: widok, CSS i skrypty',
            'shared_badges'=>'Rozmiary i styl prefiksów publikacji serwerów'
        ];
    }
    public static function enabled(string $key): bool
    {
        if (!isset(static::definitions()[$key])) return FALSE;
        if (defined('\IPS\MPCS_DISABLED_HOOKS')) {
            $disabled = array_map('trim', explode(',', (string)\IPS\MPCS_DISABLED_HOOKS));
            if (in_array('*', $disabled, TRUE) || in_array($key, $disabled, TRUE)) return FALSE;
        }
        $values = json_decode((string)\IPS\Settings::i()->mpcs_feature_hooks, TRUE);
        return !is_array($values) || !array_key_exists($key, $values) || $values[$key] === TRUE;
    }
    public static function fields($form): void
    {
        $form->addMessage('Wyłączniki diagnostyczne funkcji. Wyłączenie zachowuje dane. Prefiksy nie są synchronizowane z tagami IPS.', 'ipsMessage ipsMessage--info');
        foreach (static::definitions() as $key=>$label) {
            $field = new \IPS\Helpers\Form\YesNo('mpcs_hook_'.$key, static::enabled($key));
            $field->label = $label;
            $form->add($field);
        }
    }
    public static function values(array $values): string
    {
        $result=[];
        foreach (static::definitions() as $key=>$label) $result[$key]=(bool)$values['mpcs_hook_'.$key];
        return json_encode($result);
    }
}
