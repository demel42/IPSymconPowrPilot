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

        $this->CreateVarProfile('PowrPilot.KWh', VARIABLETYPE_FLOAT, ' KWh', 0, 0, 0, 1, '', [], $reInstall);
        $this->CreateVarProfile('PowrPilot.KW', VARIABLETYPE_FLOAT, ' KW', 0, 0, 0, 1, '', [], $reInstall);
    }
}
