<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class PowrPilot extends IPSModule
{
    use PowrPilot\StubsCommonLib;
    use PowrPilotLocalLib;

    private $ModuleDir;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->ModuleDir = __DIR__;
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterAttributeString('UpdateInfo', '');

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
        $data = utf8_decode($jmsg['Buffer']);

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
        $data = '{"modultyp":"PowrPilot","vars":[{"name":"0","homematic_name":"pp_counterIP","desc":"ip des powrpilots","type":"string","unit":"","value":"192.168.178.89"},{"name":"11","homematic_name":"pp_kwh_bezug","desc":"kwh_bezug","type":"number","unit":"KWh","value":1732.000},{"name":"12","homematic_name":"pp_kw_bezug","desc":"kw_bezug","type":"number","unit":"KW","value":0.000},{"name":"13","homematic_name":"pp_kwh_abgabe","desc":"kwh_abgabe","type":"number","unit":"KWh","value":207.000},{"name":"14","homematic_name":"pp_kw_abgabe","desc":"kw_abgabe","type":"number","unit":"KW","value":0.000},{"name":"15","homematic_name":"pp_d0_status","desc":"d0_status","type":"boolean","unit":" ","value":false}],"Systeminfo":{"MAC-Adresse":"58:bf:25:d9:d3:8d","Homematic_CCU_ip":"192.168.178.72","WLAN_ssid":"thermaqua","WLAN_Signal_dBm":"-70","sec_seit_reset":"73411","zeitpunkt":"2023.02.08 /14h01","firmware":"powrpilot_17"}}';
        $jdata = json_decode($data, true);

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

        $vars = $this->GetArrayElem($jdata, 'vars', '');
        $this->SendDebug(__FUNCTION__, 'vars=' . print_r($vars, true), 0);
        foreach ($vars as $var) {
            $ident = $this->GetArrayElem($var, 'homematic_name', '');
            $value = $this->GetArrayElem($var, 'value', '');

            $found = false;
            $skip = false;

            if ($found) {
                if ($skip) {
                    $this->SendDebug(__FUNCTION__, 'skip ident=' . $ident . ', value=' . $value . ' => no value', 0);
                } else {
                    $this->SendDebug(__FUNCTION__, 'use ident=' . $ident . ', value=' . $value, 0);
                }
            } else {
                $this->SendDebug(__FUNCTION__, 'ignore ident=' . $ident . ', value=' . $value, 0);
            }
        }

        $this->SetValue('LastUpdate', time());
    }
}

/*
{
"modultyp":"PowrPilot",
"vars":[
    {"name":"0","homematic_name":"pp_counterIP","desc":"ip des powrpilots","type":"string","unit":"","value":"192.168.178.89"},
    {"name":"11","homematic_name":"pp_kwh_bezug","desc":"kwh_bezug","type":"number","unit":"KWh","value":1732.000},
    {"name":"12","homematic_name":"pp_kw_bezug","desc":"kw_bezug","type":"number","unit":"KW","value":0.000},
    {"name":"13","homematic_name":"pp_kwh_abgabe","desc":"kwh_abgabe","type":"number","unit":"KWh","value":207.000},
    {"name":"14","homematic_name":"pp_kw_abgabe","desc":"kw_abgabe","type":"number","unit":"KW","value":0.000},
    {"name":"15","homematic_name":"pp_d0_status","desc":"d0_status","type":"boolean","unit":" ","value":false}
],
"Systeminfo":{"MAC-Adresse":"58:bf:25:d9:d3:8d","Homematic_CCU_ip":"192.168.178.72","WLAN_ssid":"thermaqua","WLAN_Signal_dBm":"-70","sec_seit_reset":"73411","zeitpunkt":"2023.02.08 /14h01","firmware":"powrpilot_17"}}
 */
