<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class PowrPilot extends IPSModule
{
    use PowrPilot\StubsCommonLib;
    use PowrPilotLocalLib;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonContruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->RequireParent('{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $vpos = 1;

        $this->MaintainVariable('PurchasePower', $this->Translate('Energy purchase'), VARIABLETYPE_FLOAT, 'PowrPilot.KWh', $vpos++, true);
        $this->MaintainVariable('PurchaseEnergy', $this->Translate('Power purchase'), VARIABLETYPE_FLOAT, 'PowrPilot.KW', $vpos++, true);

        $this->MaintainVariable('DeliveryPower', $this->Translate('Energy delivery'), VARIABLETYPE_FLOAT, 'PowrPilot.KWh', $vpos++, true);
        $this->MaintainVariable('DeliveryEnergy', $this->Translate('Power delivery'), VARIABLETYPE_FLOAT, 'PowrPilot.KW', $vpos++, true);

        $this->MaintainVariable('Switch', $this->Translate('Switching output'), VARIABLETYPE_BOOLEAN, 'PowrPilot.Switch', $vpos++, true);

        $vpos = 100;

        $this->MaintainVariable('LastMeasurement', $this->Translate('Last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('LastUpdate', $this->Translate('Last update'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('Uptime', $this->Translate('Uptime'), VARIABLETYPE_INTEGER, 'PowrPilot.sec', $vpos++, true);
        $this->MaintainVariable('WifiStrength', $this->Translate('wifi-signal'), VARIABLETYPE_INTEGER, 'PowrPilot.Wifi', $vpos++, true);

        $this->MaintainStatus(IS_ACTIVE);
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('PowrPilot');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = [
            'type'      => 'Button',
            'caption'   => 'IMPORT',
            'onClick'   => 'IPS_RequestAction(' . $this->InstanceID . ', "Import", "");',
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Expert area',
            'expanded'  => false,
            'items'     => [
                $this->GetInstallVarProfilesFormItem(),
            ],
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    private function LocalRequestAction($ident, $value)
    {
        $r = true;
        switch ($ident) {
            case 'Import':
                $this->ProcessData('');
                break;
            default:
                $r = false;
                break;
        }
        return $r;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->LocalRequestAction($ident, $value)) {
            return;
        }
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }
        switch ($ident) {
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
    }

    public function ReceiveData($msg)
    {
        $jmsg = json_decode($msg, true);
        $data = $jmsg['Buffer'];

        switch ((int) $jmsg['Type']) {
            case 0: /* Data */
                $this->SendDebug(__FUNCTION__, $jmsg['ClientIP'] . ':' . $jmsg['ClientPort'] . ' => received: ' . $data, 0);
                $rdata = $this->GetMultiBuffer('Data');
                if (substr($data, -1) == chr(4)) {
                    $ndata = $rdata . substr($data, 0, -1);
                } else {
                    $ndata = $rdata . $data;
                }
                break;
            case 1: /* Connected */
                $this->SendDebug(__FUNCTION__, $jmsg['ClientIP'] . ':' . $jmsg['ClientPort'] . ' => connected', 0);
                $ndata = '';
                break;
            case 2: /* Disconnected */
                $this->SendDebug(__FUNCTION__, $jmsg['ClientIP'] . ':' . $jmsg['ClientPort'] . ' => disonnected', 0);
                $rdata = $this->GetMultiBuffer('Data');
                if ($rdata != '') {
                    $jdata = json_decode($rdata, true);
                    if ($jdata == '') {
                        $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg() . ', data=' . $rdata, 0);
                    } else {
                        $this->ProcessData($jdata);
                    }
                }
                $ndata = '';
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'unknown Type, jmsg=' . print_r($jmsg, true), 0);
                break;
        }
        $this->SetMultiBuffer('Data', $ndata);
    }

    private function ProcessData($jdata)
    {
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($jdata, true), 0);

        $modultyp = $this->GetArrayElem($jdata, 'modultyp', '');

        $systeminfo = $this->GetArrayElem($jdata, 'Systeminfo', '');
        $this->SendDebug(__FUNCTION__, 'Systeminfo=' . print_r($systeminfo, true), 0);

        $s = $this->GetArrayElem($jdata, 'Systeminfo.zeitpunkt', '');
        if (preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+) /([0-9]+)h([0-9]+)$#', $s, $r)) {
            $tstamp = strtotime($r[1] . '-' . $r[2] . '-' . $r[3] . ' ' . $r[4] . ':' . $r[5] . ':00');
        } else {
            $this->SendDebug(__FUNCTION__, 'unable to decode date "' . $s . '"', 0);
            $tstamp = 0;
        }
        $this->SetValue('LastMeasurement', $tstamp);

        $uptime = $this->GetArrayElem($jdata, 'Systeminfo.sec_seit_reset', '');
        $this->SetValue('Uptime', $uptime);

        $rssi = $this->GetArrayElem($jdata, 'Systeminfo.WLAN_Signal_dBm', '');
        $this->SetValue('WifiStrength', $rssi);

        $this->SendDebug(__FUNCTION__, 'modultyp=' . $modultyp . ', measure=' . date('d.m.Y H:i:s', $tstamp) . ', rssi=' . $rssi . ', uptime=' . $uptime . 's', 0);

        $name2ident = [
            'pp_kwh_bezug'  => 'PurchasePower',
            'pp_kw_bezug'   => 'PurchaseEnergy',
            'pp_kwh_abgabe' => 'DeliveryPower',
            'pp_kw_abgabe'  => 'DeliveryEnergy',
            'pp_d0_status'  => 'Switch',
        ];

        $vars = $this->GetArrayElem($jdata, 'vars', '');
        $this->SendDebug(__FUNCTION__, 'vars=' . print_r($vars, true), 0);
        foreach ($vars as $var) {
            $name = $this->GetArrayElem($var, 'homematic_name', '');
            $value = $this->GetArrayElem($var, 'value', '');

            $found = false;
            $skip = false;

            if (isset($name2ident[$name])) {
                $ident = $name2ident[$name];
                if (in_array((string) $value, ['', 'inf', 'nan'])) {
                    $skip = true;
                } else {
                    $this->SetValue($ident, $value);
                }
                $found = true;
            }

            if ($found) {
                if ($skip) {
                    $this->SendDebug(__FUNCTION__, 'skip name=' . $name . ', ident=' . $ident . ', value=' . $value . ' => no value', 0);
                } else {
                    $this->SendDebug(__FUNCTION__, 'use name=' . $name . ', ident=' . $ident . ', value=' . $value, 0);
                }
            } else {
                $this->SendDebug(__FUNCTION__, 'ignore name=' . $name . ', value=' . $value, 0);
            }
        }

        $this->SetValue('LastUpdate', time());
    }
}
