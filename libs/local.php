<?php

declare(strict_types=1);

trait PowrPilotLocalLib
{
    private function GetFormStatus()
    {
        $formStatus = $this->GetCommonFormStatus();

        return $formStatus;
    }

    public static $STATUS_INVALID = 0;
    public static $STATUS_VALID = 1;
    public static $STATUS_RETRYABLE = 2;

    private function CheckStatus()
    {
        switch ($this->GetStatus()) {
            case IS_ACTIVE:
                $class = self::$STATUS_VALID;
                break;
            default:
                $class = self::$STATUS_INVALID;
                break;
        }

        return $class;
    }

    private function InstallVarProfiles(bool $reInstall = false)
    {
        if ($reInstall) {
            $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);
        }

        $this->CreateVarProfile('PowrPilot.Wifi', VARIABLETYPE_INTEGER, ' dBm', 0, 0, 0, 0, 'Intensity', [], $reInstall);
        $this->CreateVarProfile('PowrPilot.sec', VARIABLETYPE_INTEGER, ' s', 0, 0, 0, 0, 'Clock', [], $reInstall);

        $this->CreateVarProfile('PowrPilot.kWh', VARIABLETYPE_FLOAT, ' kWh', 0, 0, 0, 1, '', [], $reInstall);
        $this->CreateVarProfile('PowrPilot.kW', VARIABLETYPE_FLOAT, ' kW', 0, 0, 0, 1, '', [], $reInstall);

        $associations = [
            ['Wert' => false, 'Name' => $this->Translate('Off'), 'Farbe' => -1],
            ['Wert' => true, 'Name' => $this->Translate('On'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('PowrPilot.Switch', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);
    }
}
