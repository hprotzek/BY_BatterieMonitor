<?
class BatterieMonitor extends IPSModule
{

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        
        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        $this->RegisterPropertyString("HintergrundFarbcode", "000000");
        $this->RegisterPropertyString("TextFarbcode", "FFFFFF");
        $this->RegisterPropertyString("TextSize", "12");
        $this->RegisterPropertyInteger("Intervall", 21600);
        $this->RegisterTimer("BMON_UpdateTimer", 0, 'BMON_Update($_IPS[\'TARGET\']);');
    }

    public function Destroy()
    {
        $this->UnregisterTimer("BMON_UpdateTimer");
        
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        
        //Variablenprofil erstellen
        $this->RegisterProfileBooleanEx("BMON.NeinJa", "Battery", "", "", Array(
                                             Array(false, "Nein",  "Battery", 0x00FF00),
                                             Array(true, "Ja",  "Warning", 0xFF0000)
        ));
        
        //Variablen anlegen und einstellen
        $this->RegisterVariableInteger("BatteryAktorsAnzahlVAR", "Batterie Aktoren - Anzahl");
        $this->RegisterVariableInteger("BatteryLowAnzahlVAR", "Batterie leer - Anzahl");
        $this->RegisterVariableBoolean("BatteryLowExistVAR", "Batterie leer - vorhanden", "BMON.NeinJa");
		    $this->RegisterVariableString("TabelleBatteryAlleVAR", "Tabelle - Batterie Aktoren ALLE", "~HTMLBox");
		    $this->RegisterVariableString("TabelleBatteryLowVAR", "Tabelle - Batterie Aktoren LEER", "~HTMLBox");
		    IPS_SetIcon($this->GetIDForIdent("BatteryAktorsAnzahlVAR"), "Battery");
		    IPS_SetIcon($this->GetIDForIdent("BatteryLowAnzahlVAR"), "Battery");
		    IPS_SetIcon($this->GetIDForIdent("BatteryLowExistVAR"), "Battery");
		    IPS_SetIcon($this->GetIDForIdent("TabelleBatteryAlleVAR"), "Battery");
		    IPS_SetIcon($this->GetIDForIdent("TabelleBatteryLowVAR"), "Battery");
		        
		    //Timer erstellen
        $this->SetTimerInterval("BMON_UpdateTimer", $this->ReadPropertyInteger("Intervall"));
        		
     		//Update
     		$this->Update();
    }

    public function Update()
    {
				$Batterien_AR = $this->ReadBatteryStates();
				$BATcountAlle = @count($TestAR["Alle"]);
				$BATcountLeer = @count($TestAR["Leer"]);
				$this->SetValueInteger("BatteryAktorsAnzahlVAR", $BATcountAlle);
				$this->SetValueInteger("BatteryLowAnzahlVAR", $BATcountLeer);
				if ($BATcountLeer == 0)
				{
						$this->SetValueBoolean("BatteryLowExistVAR", false);
				}
				else
				{
						$this->SetValueBoolean("BatteryLowExistVAR", true);
				}
				$this->HTMLausgabeGenerieren($Batterien_AR, "Alle");
				$this->HTMLausgabeGenerieren($Batterien_AR, "Leer");
    }
    
    public function Alle_Auslesen()
    {
    		$Batterien_AR = $this->ReadBatteryStates();
    		$this->HTMLausgabeGenerieren($Batterien_AR, "Alle");
    		return $Batterien_AR["Alle"];
    }
    
    public function Leere_Auslesen()
    {
    		$Batterien_AR = $this->ReadBatteryStates();
    		$this->HTMLausgabeGenerieren($Batterien_AR, "Leer");
    		return $Batterien_AR["Leer"];
    }
    
    private function ReadBatteryStates()
    {
    		$InstanzIDsListAll[] = IPS_GetInstanceListByModuleID("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}");  // HomeMatic
    		$InstanzIDsListAll[] = IPS_GetInstanceListByModuleID("{101352E1-88C7-4F16-998B-E20D50779AF6}");  // Z-Wave
    		
    		foreach ($InstanzIDsListAll as $InstanzIDsList)
    		{
						foreach ($InstanzIDsList as $InstanzID)
						{
						    //HomeMatic
						    $VarID = @IPS_GetObjectIDByIdent('LOWBAT', $InstanzID);
								if ($VarID !== false)
								{
										$Batterien_AR["Alle"][] = IPS_GetName($InstanzID);
										$LowBat = GetValueBoolean($VarID);
										if ($LowBat === true)
										{
									   		$Batterien_AR["Leer"][] = IPS_GetName($InstanzID);
										}
						  	}
						  	
						  	//Z-Wave
						  	$VarID = @IPS_GetObjectIDByIdent('BatteryLowVariable', $InstanzID);
								if ($VarID !== false)
								{
										$Batterien_AR["Alle"][] = IPS_GetName($InstanzID);
										$LowBat = GetValueBoolean($VarID);
										if ($LowBat === true)
										{
									   		$Batterien_AR["Leer"][] = IPS_GetName($InstanzID);
										}
						  	}
						}
				}
				return $Batterien_AR;
    }

		private function HTMLausgabeGenerieren($BatterienAR, $AlleLeer)
		{
				$HintergrundFarbcode = $this->ReadPropertyString("HintergrundFarbcode");
				$TextFarbcode = $this->ReadPropertyString("TextFarbcode");
				$TextSize = $this->ReadPropertyString("TextSize");
				$HTML_CSS_Style = '<style type="text/css">
				.bt {border-collapse;border-spacing:4;}
				.bt td'.$this->InstanceID.' {font-family:Arial, sans-serif;font-size:'.$this->ReadPropertyString("TextSize").'px;color:#'.$TextFarbcode.';padding:1px 10px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;}
				.bt th'.$this->InstanceID.' {font-family:Arial, sans-serif;font-size:'.$this->ReadPropertyString("TextSize").'px;color:#'.$TextFarbcode.';font-weigth:normal;padding:1px 10px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;}
				.bt .tb-title'.$this->InstanceID.'{font-size:'.$this->ReadPropertyString("TextSize").'px;font-weight:bold;background-color:#'.$HintergrundFarbcode.';color:#'.$TextFarbcode.';text-align:center}
				.bt .tb-content'.$this->InstanceID.'{font-size:'.$this->ReadPropertyString("TextSize").'px;background-color:#'.$HintergrundFarbcode.';color:#'.$TextFarbcode.';text-align:center}
				</style>';
			
				$TitelAR = array("Aktor","Batterie");
				$HTML = '<html>'.$HTML_CSS_Style;
				$HTML .= '<table class="bt">';
				$HTML .= '<tr><th class="tb-title'.$this->InstanceID.'">'.$TitelAR[0].'</th><th class="tb-title'.$this->InstanceID.'">'.$TitelAR[1].'</th></tr>';
				
				if ($AlleLeer == "Alle") {
						for ($h=0; $h<count($BatterienAR["Alle"]); $h++) {
								if (($h == 0) OR ($h == 1) OR ($h == 2)) {
								   $HTML .= '<tr><th class="tb-content'.$this->InstanceID.'">'.$BatterienAR["Alle"][$h].'</th><th class="tb-content'.$this->InstanceID.'">'.$BatterienAR["Alle"][$h].'</th></tr>';
								}
						}
						$HTML .= '</table></html>';
						$this->SetValueString("TabelleBatteryAlleVAR", $HTML);
				}
				elseif ($AlleLeer == "Leer") {
						if (isset($BatterienAR["Leer"]))
						{
								for ($h=0; $h<count($BatterienAR["Leer"]); $h++) {
										if (($h == 0) OR ($h == 1) OR ($h == 2)) {
										   $HTML .= '<tr><th class="tb-content'.$this->InstanceID.'">'.$BatterienAR["Leer"][$h].'</th><th class="tb-content'.$this->InstanceID.'">'.$BatterienAR["Leer"][$h].'</th></tr>';
										}
								}
						}
						else
						{
								$HTML .= '<tr><th colspan="2">Keine Aktoren mit leeren Batterien vorhanden!</th></tr>';
						}
						$HTML .= '</table></html>';
						$this->SetValueString("TabelleBatteryLowVAR", $HTML);
				}
		}

    private function SetValueBoolean($Ident, $Value)
    {
        $ID = $this->GetIDForIdent($Ident);
        if (GetValueBoolean($ID) <> $Value)
        {
            SetValueBoolean($ID, boolval($Value));
            return true;
        }
        return false;
    }

    private function SetValueInteger($Ident, $value)
    {
        $id = $this->GetIDForIdent($Ident);
        if (GetValueInteger($id) <> $value)
        {
            SetValueInteger($id, $value);
            return true;
        }
        return false;
    }
    
    private function SetValueString($Ident, $Value)
    {
        $ID = $this->GetIDForIdent($Ident);
        if (GetValueString($ID) <> $Value)
        {
            SetValueString($ID, strval($Value));
            return true;
        }
        return false;
    }
    
    protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 0);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 0)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
    }
    
    protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }
    
    protected function RegisterTimer($Name, $Interval, $Script)
    {
        $id = @IPS_GetObjectIDByIdent($Name, $this->InstanceID);
        if ($id === false)
            $id = 0;


        if ($id > 0)
        {
            if (!IPS_EventExists($id))
                throw new Exception("Ident with name " . $Name . " is used for wrong object type", E_USER_WARNING);

            if (IPS_GetEvent($id)['EventType'] <> 1)
            {
                IPS_DeleteEvent($id);
                $id = 0;
            }
        }

        if ($id == 0)
        {
            $id = IPS_CreateEvent(1);
            IPS_SetParent($id, $this->InstanceID);
            IPS_SetIdent($id, $Name);
        }
        IPS_SetName($id, $Name);
        IPS_SetHidden($id, true);
        IPS_SetEventScript($id, $Script);
        if ($Interval > 0)
        {
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $Interval);

            IPS_SetEventActive($id, true);
        } else
        {
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);

            IPS_SetEventActive($id, false);
        }
    }

    protected function UnregisterTimer($Name)
    {
        $id = @IPS_GetObjectIDByIdent($Name, $this->InstanceID);
        if ($id > 0)
        {
            if (!IPS_EventExists($id))
                throw new Exception('Timer not present', E_USER_NOTICE);
            IPS_DeleteEvent($id);
        }
    }
    
    protected function SetTimerInterval($Name, $Interval)
    {
        $id = @IPS_GetObjectIDByIdent($Name, $this->InstanceID);
        if ($id === false)
            throw new Exception('Timer not present', E_USER_WARNING);
        if (!IPS_EventExists($id))
            throw new Exception('Timer not present', E_USER_WARNING);

        $Event = IPS_GetEvent($id);

        if ($Interval < 1)
        {
            if ($Event['EventActive'])
                IPS_SetEventActive($id, false);
        }
        else
        {
            if ($Event['CyclicTimeValue'] <> $Interval)
                IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $Interval);
            if (!$Event['EventActive'])
                IPS_SetEventActive($id, true);
        }
    }
}
?>