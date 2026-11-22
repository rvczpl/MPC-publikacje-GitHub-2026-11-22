<?php
namespace IPS\mpcservers\Modules\Core;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
/** IPS5 badge with a strictly validated, per-option RGB colour. */
class PrefixBadge extends \IPS\Helpers\Badge
{
    protected string $colour;
    protected string $rotationGroup;
    public function __construct(string $label, string $colour, string $rotationGroup = '', bool $version = FALSE)
    {
        parent::__construct('', $label, '', '', ($version ? array('mpcs-b','mpcs-version') : array('mpcs-b')));
        $this->rotationGroup=$rotationGroup;
        $this->colour=preg_match('/^#[a-f0-9]{6}$/iD',$colour)?$colour:'#1d4ed8';
    }
    public function __toString(): string
    {
        return preg_replace('/<(span|div)\b/', '<$1 data-mpcs-rotate-group="'.htmlspecialchars($this->rotationGroup,ENT_QUOTES,'UTF-8').'" style="background:'.$this->colour.';color:'.Servers::foreground($this->colour).'"', parent::__toString(), 1);
    }
}
