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
        $this->RegisterPropertyString("TextOKFarbcode", "00FF00");
        $this->RegisterPropertyString("TextLOWFarbcode", "FF0000");
        $this->RegisterPropertyString("TextSize", "14");
        $this->RegisterPropertyString("TextAusrichtungDD", "mitte");
        $this->RegisterPropertyString("ArraySortierWert", "name");
        $this->RegisterPropertyBoolean("NamenAktorObjektCB", true);
        $this->RegisterPropertyBoolean("NamenParentObjekt1CB", false);
        $this->RegisterPropertyBoolean("NamenParentObjekt2CB", false);
        $this->RegisterPropertyBoolean("NamenParentObjekt3CB", false);
        $this->RegisterPropertyString("NameParentTabelle1TB", "Etage");
        $this->RegisterPropertyString("NameParentTabelle2TB", "Raum");
        $this->RegisterPropertyString("NameParentTabelle3TB", "Gebaeude");
        $this->RegisterPropertyInteger("ParentNr1NS", 1);
        $this->RegisterPropertyInteger("ParentNr2NS", 2);
        $this->RegisterPropertyInteger("ParentNr3NS", 3);
        $this->RegisterPropertyInteger("Intervall", 21600);
        $this->RegisterPropertyInteger("BenachrichtigungsVar", 0);
        $this->RegisterPropertyInteger("WebFrontInstanceID", 0);
        $this->RegisterPropertyInteger("SmtpInstanceID", 0);
        $this->RegisterPropertyInteger("EigenesSkriptID", 0);
        $this->RegisterPropertyBoolean("PushMsgAktiv", false);
        $this->RegisterPropertyBoolean("EMailMsgAktiv", false);
        $this->RegisterPropertyBoolean("EigenesSkriptAktiv", false);
        $this->RegisterPropertyBoolean("BatterieBenachrichtigungCBOX", false);
        $this->RegisterPropertyString("BatterieBenachrichtigungTEXT", "Der Aktor -�AKTORNAME- vom Hersteller -�AKTORHERSTELLER- mit der ID -�AKTORID- hat eine leere Batterie!");
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
            Array(false, "Nein", "Battery", 0x00FF00),
            Array(true, "Ja", "Warning", 0xFF0000)
        ));

        //Fehlerhafte Konfiguration melden
        if (($this->ReadPropertyBoolean("PushMsgAktiv") === true) AND ($this->ReadPropertyInteger("WebFrontInstanceID") == "")) {
            $this->SetStatus(201);
        } elseif (($this->ReadPropertyBoolean("EMailMsgAktiv") === true) AND ($this->ReadPropertyInteger("SmtpInstanceID") == "")) {
            $this->SetStatus(202);
        } elseif (($this->ReadPropertyBoolean("EigenesSkriptAktiv") === true) AND ($this->ReadPropertyInteger("EigenesSkriptID") == "")) {
            $this->SetStatus(203);
        } elseif ((($this->ReadPropertyBoolean("PushMsgAktiv") === false) AND ($this->ReadPropertyBoolean("EMailMsgAktiv") === false) AND ($this->ReadPropertyBoolean("EigenesSkriptAktiv") === false)) AND (($this->ReadPropertyBoolean("BatterieBenachrichtigungCBOX") === true))) {
            $this->SetStatus(204);
        } else {
            $this->SetStatus(102);
        }

        //Variablen anlegen und einstellen
        $this->RegisterVariableInteger("BatteryAktorsAnzahlVAR", "Batterie Aktoren - Gesamt");
        $this->RegisterVariableInteger("BatteryLowAnzahlVAR", "Batterie Aktoren - Leer");
        $this->RegisterVariableBoolean("BatteryLowExistVAR", "Batterie Aktoren - Leere vorhanden", "BMON.NeinJa");
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
        $BATcountAlle = @count($Batterien_AR["Alle"]);
        $BATcountLeer = @count($Batterien_AR["Leer"]);
        $this->SetValueInteger("BatteryAktorsAnzahlVAR", $BATcountAlle);
        $this->SetValueInteger("BatteryLowAnzahlVAR", $BATcountLeer);
        if ($BATcountLeer == 0) {
            $this->SetValueBoolean("BatteryLowExistVAR", false);
        } else {
            $this->SetValueBoolean("BatteryLowExistVAR", true);
            if ($this->ReadPropertyBoolean("BatterieBenachrichtigungCBOX") == true) {
                $this->Benachrichtigung($Batterien_AR["Leer"]);
            }
        }
        $this->HTMLausgabeGenerieren($Batterien_AR, "Alle");
        $this->HTMLausgabeGenerieren($Batterien_AR, "Leer");
        return true;
    }

    public function Alle_Auslesen()
    {
        $Batterien_AR = $this->ReadBatteryStates();
        $BATcountAlle = @count($Batterien_AR["Alle"]);
        $BATcountLeer = @count($Batterien_AR["Leer"]);
        $this->SetValueInteger("BatteryAktorsAnzahlVAR", $BATcountAlle);
        $this->SetValueInteger("BatteryLowAnzahlVAR", $BATcountLeer);
        if ($BATcountLeer == 0) {
            $this->SetValueBoolean("BatteryLowExistVAR", false);
        } else {
            $this->SetValueBoolean("BatteryLowExistVAR", true);
        }

        $this->HTMLausgabeGenerieren($Batterien_AR, "Alle");
        if (isset($Batterien_AR["Alle"])) {
            return $Batterien_AR["Alle"];
        } else {
            return false;
        }
    }

    public function Leere_Auslesen()
    {
        $Batterien_AR = $this->ReadBatteryStates();
        $BATcountAlle = @count($Batterien_AR["Alle"]);
        $BATcountLeer = @count($Batterien_AR["Leer"]);
        $this->SetValueInteger("BatteryAktorsAnzahlVAR", $BATcountAlle);
        $this->SetValueInteger("BatteryLowAnzahlVAR", $BATcountLeer);
        if ($BATcountLeer == 0) {
            $this->SetValueBoolean("BatteryLowExistVAR", false);
        } else {
            $this->SetValueBoolean("BatteryLowExistVAR", true);
        }

        $this->HTMLausgabeGenerieren($Batterien_AR, "Leer");
        if (isset($Batterien_AR["Leer"])) {
            return $Batterien_AR["Leer"];
        } else {
            return false;
        }
    }

    private function ReadBatteryStates()
    {
        $InstanzIDsListAll[] = IPS_GetInstanceListByModuleID("{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}");  // FHT
        $InstanzIDsListAll[] = IPS_GetInstanceListByModuleID("{2FD7576A-D2AD-47EE-9779-A502F23CABB3}");  // FS20 HMS
        $InstanzIDsListAll[] = IPS_GetInstanceListByModuleID("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}");  // HomeMatic
        $InstanzIDsListAll[] = IPS_GetInstanceListByModuleID("{101352E1-88C7-4F16-998B-E20D50779AF6}");  // Z-Wave
        $a = 0;
        $l = 0;
        foreach ($InstanzIDsListAll as $InstanzIDsList) {
            foreach ($InstanzIDsList as $InstanzID) {
                $InstanzHersteller = IPS_GetInstance($InstanzID);
                $InstanzHersteller = $InstanzHersteller["ModuleInfo"]["ModuleName"];
                switch ($InstanzHersteller) {
                    case "FHT":
                        $InstanzHersteller = "FHT";
                        break;
                    case "HMS":
                        $InstanzHersteller = "HMS";
                        break;
                    case "HomeMatic Device":
                        $InstanzHersteller = "HomeMatic";
                        break;
                    case "Z-Wave Module":
                        $InstanzHersteller = "Z-Wave";
                        break;
                }

                //FHT
                $VarID = @IPS_GetObjectIDByIdent('LowBatteryVar', $InstanzID);
                if (($VarID !== false) AND ($InstanzHersteller == "FHT")) {
                    $Var = IPS_GetVariable($VarID);
                    $VarLastUpdated = $Var["VariableUpdated"];
                    $VarLastUpdatedDiffSek = time() - $VarLastUpdated;
                    $DeviceID = IPS_GetProperty($InstanzID, "Address");
                    $LowBat = GetValueBoolean($VarID);
                    if ($LowBat === true) {
                        $Batterien_AR["Alle"][$a]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Alle"][$a]["Batterie"] = "LEER";
                        $Batterien_AR["Alle"][$a]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Alle"][$a]["ID"] = $DeviceID;
                        $Batterien_AR["Alle"][$a]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $Batterien_AR["Leer"][$l]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Leer"][$l]["Batterie"] = "LEER";
                        $Batterien_AR["Leer"][$l]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Leer"][$l]["ID"] = $DeviceID;
                        $Batterien_AR["Leer"][$l]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Leer"][$l]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Leer"][$l]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $a++;
                        $l++;
                    } else {
                        $Batterien_AR["Alle"][$a]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Alle"][$a]["Batterie"] = "OK";
                        $Batterien_AR["Alle"][$a]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Alle"][$a]["ID"] = $DeviceID;
                        $Batterien_AR["Alle"][$a]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $a++;
                    }
                }

                //FS20 HMS
                $VarID = @IPS_GetObjectIDByIdent('LowBatteryVar', $InstanzID);
                if (($VarID !== false) AND ($InstanzHersteller == "HMS")) {
                    $Var = IPS_GetVariable($VarID);
                    $VarLastUpdated = $Var["VariableUpdated"];
                    $VarLastUpdatedDiffSek = time() - $VarLastUpdated;
                    $DeviceID = IPS_GetProperty($InstanzID, "DeviceID");
                    $LowBat = GetValueBoolean($VarID);
                    if ($LowBat === true) {
                        $Batterien_AR["Alle"][$a]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt") == true)
                            if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                                $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                                $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                                $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                            }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Alle"][$a]["Batterie"] = "LEER";
                        $Batterien_AR["Alle"][$a]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Alle"][$a]["ID"] = $DeviceID;
                        $Batterien_AR["Alle"][$a]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $Batterien_AR["Leer"][$l]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Leer"][$l]["Batterie"] = "LEER";
                        $Batterien_AR["Leer"][$l]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Leer"][$l]["ID"] = $DeviceID;
                        $Batterien_AR["Leer"][$l]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Leer"][$l]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Leer"][$l]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $a++;
                        $l++;
                    } else {
                        $Batterien_AR["Alle"][$a]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Alle"][$a]["Batterie"] = "OK";
                        $Batterien_AR["Alle"][$a]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Alle"][$a]["ID"] = $DeviceID;
                        $Batterien_AR["Alle"][$a]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $a++;
                    }
                }

                //HomeMatic LOWBAT
                $VarID = @IPS_GetObjectIDByIdent('LOWBAT', $InstanzID);
                if (($VarID !== false) AND ($InstanzHersteller == "HomeMatic")) {
                    $Var = IPS_GetVariable($VarID);
                    $VarLastUpdated = $Var["VariableUpdated"];
                    $VarLastUpdatedDiffSek = time() - $VarLastUpdated;
                    $DeviceID = substr(IPS_GetProperty($InstanzID, "Address"), 0, -2);
                    $LowBat = GetValueBoolean($VarID);
                    if ($LowBat === true) {
                        $Batterien_AR["Alle"][$a]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Alle"][$a]["Batterie"] = "LEER";
                        $Batterien_AR["Alle"][$a]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Alle"][$a]["ID"] = $DeviceID;
                        $Batterien_AR["Alle"][$a]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $Batterien_AR["Leer"][$l]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Leer"][$l]["Batterie"] = "LEER";
                        $Batterien_AR["Leer"][$l]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Leer"][$l]["ID"] = $DeviceID;
                        $Batterien_AR["Leer"][$l]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Leer"][$l]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Leer"][$l]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $a++;
                        $l++;
                    } else {
                        $Batterien_AR["Alle"][$a]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Alle"][$a]["Batterie"] = "OK";
                        $Batterien_AR["Alle"][$a]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Alle"][$a]["ID"] = $DeviceID;
                        $Batterien_AR["Alle"][$a]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $a++;
                    }
                }

                //HomeMatic FAULT_REPORTING
                $VarID = @IPS_GetObjectIDByIdent('FAULT_REPORTING', $InstanzID);
                if (($VarID !== false) AND ($InstanzHersteller == "HomeMatic")) {
                    $Var = IPS_GetVariable($VarID);
                    $VarLastUpdated = $Var["VariableUpdated"];
                    $VarLastUpdatedDiffSek = time() - $VarLastUpdated;
                    $DeviceID = substr(IPS_GetProperty($InstanzID, "Address"), 0, -2);
                    $LowBat = GetValueInt($VarID);
                    if ($LowBat === 6) {
                        $Batterien_AR["Alle"][$a]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Alle"][$a]["Batterie"] = "LEER";
                        $Batterien_AR["Alle"][$a]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Alle"][$a]["ID"] = $DeviceID;
                        $Batterien_AR["Alle"][$a]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $Batterien_AR["Leer"][$l]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Leer"][$l]["Batterie"] = "LEER";
                        $Batterien_AR["Leer"][$l]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Leer"][$l]["ID"] = $DeviceID;
                        $Batterien_AR["Leer"][$l]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Leer"][$l]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Leer"][$l]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $a++;
                        $l++;
                    } else {
                        $Batterien_AR["Alle"][$a]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Alle"][$a]["Batterie"] = "OK";
                        $Batterien_AR["Alle"][$a]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Alle"][$a]["ID"] = $DeviceID;
                        $Batterien_AR["Alle"][$a]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $a++;
                    }
                }

                //Z-Wave
                $VarID = @IPS_GetObjectIDByIdent('BatteryLowVariable', $InstanzID);
                if (($VarID !== false) AND ($InstanzHersteller == "Z-Wave")) {
                    $Var = IPS_GetVariable($VarID);
                    $VarLastUpdated = $Var["VariableUpdated"];
                    $VarLastUpdatedDiffSek = time() - $VarLastUpdated;
                    $DeviceID = IPS_GetProperty($InstanzID, "NodeID");
                    $LowBat = GetValueBoolean($VarID);
                    if ($LowBat === true) {
                        $Batterien_AR["Alle"][$a]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Alle"][$a]["Batterie"] = "LEER";
                        $Batterien_AR["Alle"][$a]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Alle"][$a]["ID"] = $DeviceID;
                        $Batterien_AR["Alle"][$a]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $Batterien_AR["Leer"][$l]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
                            $Batterien_AR["Leer"][$l][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Leer"][$l]["Batterie"] = "LEER";
                        $Batterien_AR["Leer"][$l]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Leer"][$l]["ID"] = $DeviceID;
                        $Batterien_AR["Leer"][$l]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Leer"][$l]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Leer"][$l]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $a++;
                        $l++;
                    } else {
                        $Batterien_AR["Alle"][$a]["Name"] = $this->Umlaute_Ersetzen(IPS_GetName($InstanzID));
                        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr1NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));

                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {

                            $ParentID = $this->ParentIDermitteln("ParentNr2NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                            $ParentID = $this->ParentIDermitteln("ParentNr3NS", $InstanzID);
                            $ParentNameTabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabell3TB"));
                            $Batterien_AR["Alle"][$a][$ParentNameTabelle] = $this->Umlaute_Ersetzen(IPS_GetName($ParentID));
                        }
                        $Batterien_AR["Alle"][$a]["Batterie"] = "OK";
                        $Batterien_AR["Alle"][$a]["Hersteller"] = $InstanzHersteller;
                        $Batterien_AR["Alle"][$a]["ID"] = $DeviceID;
                        $Batterien_AR["Alle"][$a]["Hersteller_ID"] = $InstanzHersteller . " - " . $DeviceID;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateTimestamp"] = $VarLastUpdated;
                        $Batterien_AR["Alle"][$a]["LetztesVarUpdateVorSek"] = $VarLastUpdatedDiffSek;
                        $a++;
                    }
                }
            }
        }

        if (isset($Batterien_AR)) {
            //Array sortieren, doppelte Eintr�ge entfernen und neu durchnummerieren
            foreach ($Batterien_AR["Alle"] as $nr => $inhalt) {
                $nameALLE[$nr] = strtolower($inhalt["Name"]);
                if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                    $nameParent1ALLE[$nr] = $this->Umlaute_Ersetzen(strtolower($inhalt[$this->ReadPropertyString("NameParentTabelle1TB")]));
                }
                if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {
                    $nameParent2ALLE[$nr] = $this->Umlaute_Ersetzen(strtolower($inhalt[$this->ReadPropertyString("NameParentTabelle2TB")]));
                }
                if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                    $nameParent3ALLE[$nr] = $this->Umlaute_Ersetzen(strtolower($inhalt[$this->ReadPropertyString("NameParentTabelle3TB")]));
                }
                $batterieALLE[$nr] = strtolower($inhalt["Batterie"]);
                $herstellerALLE[$nr] = strtolower($inhalt["Hersteller"]);
                $idALLE[$nr] = strtolower($inhalt["ID"]);
                $herstelleridALLE[$nr] = strtolower($inhalt["Hersteller_ID"]);
                $lastupdatetsALLE[$nr] = strtolower($inhalt["LetztesVarUpdateTimestamp"]);
                $lastupdatevsALLE[$nr] = strtolower($inhalt["LetztesVarUpdateVorSek"]);
            }

            //Nach was soll das Array sortiert werden?
            if ($this->ReadPropertyString("ArraySortierWert") == "name") {
                array_multisort($nameALLE, SORT_ASC, $Batterien_AR["Alle"]);
            } elseif ($this->ReadPropertyString("ArraySortierWert") == "parentname1") {
                if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                    array_multisort($nameParent1ALLE, SORT_ASC, $Batterien_AR["Alle"]);
                } else {
                    array_multisort($nameALLE, SORT_ASC, $Batterien_AR["Alle"]);
                }
            } elseif ($this->ReadPropertyString("ArraySortierWert") == "parentname2") {
                if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {
                    array_multisort($nameParent2ALLE, SORT_ASC, $Batterien_AR["Alle"]);
                } else {
                    array_multisort($nameALLE, SORT_ASC, $Batterien_AR["Alle"]);
                }
            } elseif ($this->ReadPropertyString("ArraySortierWert") == "parentname3") {
                if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                    array_multisort($nameParent3ALLE, SORT_ASC, $Batterien_AR["Alle"]);
                } else {
                    array_multisort($nameALLE, SORT_ASC, $Batterien_AR["Alle"]);
                }
            } elseif ($this->ReadPropertyString("ArraySortierWert") == "batterie") {
                array_multisort($batterieALLE, SORT_ASC, $Batterien_AR["Alle"]);
            } elseif ($this->ReadPropertyString("ArraySortierWert") == "hersteller") {
                array_multisort($herstellerALLE, SORT_ASC, $Batterien_AR["Alle"]);
            } elseif ($this->ReadPropertyString("ArraySortierWert") == "id") {
                array_multisort($idALLE, SORT_ASC, $Batterien_AR["Alle"]);
            } elseif ($this->ReadPropertyString("ArraySortierWert") == "letztesupdts") {
                array_multisort($lastupdatetsALLE, SORT_ASC, $Batterien_AR["Alle"]);
            }

            $Batterien_AR["Alle"] = $this->Array_UniqueBySubitem_Sort($Batterien_AR["Alle"], "Hersteller_ID");
            $Batterien_AR["Alle"] = array_merge($Batterien_AR["Alle"]);

            if (isset($Batterien_AR["Leer"])) {
                foreach ($Batterien_AR["Leer"] as $nr => $inhalt) {
                    $nameLEER[$nr] = strtolower($inhalt["Name"]);
                    if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                        $nameParent1LEER[$nr] = $this->Umlaute_Ersetzen(strtolower($inhalt[$this->ReadPropertyString("NameParentTabelle1TB")]));
                    }
                    if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {
                        $nameParent2LEER[$nr] = $this->Umlaute_Ersetzen(strtolower($inhalt[$this->ReadPropertyString("NameParentTabelle2TB")]));
                    }
                    if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                        $nameParent3LEER[$nr] = $this->Umlaute_Ersetzen(strtolower($inhalt[$this->ReadPropertyString("NameParentTabelle3TB")]));
                    }
                    $batterieLEER[$nr] = strtolower($inhalt["Batterie"]);
                    $herstellerLEER[$nr] = strtolower($inhalt["Hersteller"]);
                    $idLEER[$nr] = strtolower($inhalt["ID"]);
                    $herstelleridLEER[$nr] = strtolower($inhalt["Hersteller_ID"]);
                    $lastupdatetsLEER[$nr] = strtolower($inhalt["LetztesVarUpdateTimestamp"]);
                    $lastupdatevsLEER[$nr] = strtolower($inhalt["LetztesVarUpdateVorSek"]);
                }
                //Nach was soll das Array sortiert werden?
                if ($this->ReadPropertyString("ArraySortierWert") == "name") {
                    array_multisort($nameLEER, SORT_ASC, $Batterien_AR["Leer"]);
                } elseif ($this->ReadPropertyString("ArraySortierWert") == "parentname1") {
                    if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                        array_multisort($nameParent1LEER, SORT_ASC, $Batterien_AR["Leer"]);
                    } else {
                        array_multisort($nameLEER, SORT_ASC, $Batterien_AR["Leer"]);
                    }
                } elseif ($this->ReadPropertyString("ArraySortierWert") == "parentname2") {
                    if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {
                        array_multisort($nameParent2LEER, SORT_ASC, $Batterien_AR["Leer"]);
                    } else {
                        array_multisort($nameLEER, SORT_ASC, $Batterien_AR["Leer"]);
                    }
                } elseif ($this->ReadPropertyString("ArraySortierWert") == "parentname3") {
                    if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                        array_multisort($nameParent3LEER, SORT_ASC, $Batterien_AR["Leer"]);
                    } else {
                        array_multisort($nameLEER, SORT_ASC, $Batterien_AR["Leer"]);
                    }
                } elseif ($this->ReadPropertyString("ArraySortierWert") == "batterie") {
                    array_multisort($batterieLEER, SORT_ASC, $Batterien_AR["Leer"]);
                } elseif ($this->ReadPropertyString("ArraySortierWert") == "hersteller") {
                    array_multisort($herstellerLEER, SORT_ASC, $Batterien_AR["Leer"]);
                } elseif ($this->ReadPropertyString("ArraySortierWert") == "id") {
                    array_multisort($idLEER, SORT_ASC, $Batterien_AR["Leer"]);
                } elseif ($this->ReadPropertyString("ArraySortierWert") == "letztesupdts") {
                    array_multisort($lastupdatetsLEER, SORT_ASC, $Batterien_AR["Leer"]);
                }
                $Batterien_AR["Leer"] = $this->Array_UniqueBySubitem_Sort($Batterien_AR["Leer"], "Hersteller_ID");
                $Batterien_AR["Leer"] = array_merge($Batterien_AR["Leer"]);
            }
            return $Batterien_AR;
        } else {
            return false;
        }
    }

    private function HTMLausgabeGenerieren($BatterienAR, $AlleLeer)
    {
        $ParentName1Tabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
        $ParentName2Tabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
        $ParentName3Tabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
        $HintergrundFarbcode = $this->ReadPropertyString("HintergrundFarbcode");
        $TextFarbcode = $this->ReadPropertyString("TextFarbcode");
        $TextFarbcodeOK = $this->ReadPropertyString("TextOKFarbcode");
        $TextFarbcodeLEER = $this->ReadPropertyString("TextLOWFarbcode");
        $TextSize = $this->ReadPropertyString("TextSize");
        $TextSizeTitle = $TextSize + 2;
        switch ($this->ReadPropertyString("TextAusrichtungDD")) {
            case "links":
                $Textausrichtung = "text-align:left;";
                break;
            case "mitte":
                $Textausrichtung = "text-align:center;";
                break;
            case "rechts":
                $Textausrichtung = "text-align:right;";
                break;
        }
        $HTML_CSS_Style = '<style type="text/css">
				.bt {border-collapse;border-spacing:4;}
				.bt td' . $this->InstanceID . ' {font-family:Arial, sans-serif;font-size:' . $TextSize . 'px;color:#' . $TextFarbcode . ';padding:1px 10px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;}
				.bt th' . $this->InstanceID . ' {font-family:Arial, sans-serif;font-size:' . $TextSize . 'px;color:#' . $TextFarbcode . ';padding:1px 10px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;}
				.bt .tb-title' . $this->InstanceID . '{font-size:' . $TextSizeTitle . 'px;padding:2mm;background-color:#' . $HintergrundFarbcode . ';color:#' . $TextFarbcode . '}
				.bt .tb-content' . $this->InstanceID . '{font-size:' . $TextSize . 'px;padding:1mm;' . $Textausrichtung . 'background-color:#' . $HintergrundFarbcode . ';color:#' . $TextFarbcode . '}
				.bt .tb-contentOK' . $this->InstanceID . '{font-size:' . $TextSize . 'px;padding:1mm;' . $Textausrichtung . 'background-color:#' . $HintergrundFarbcode . ';color:#' . $TextFarbcodeOK . '}
				.bt .tb-contentLOW' . $this->InstanceID . '{font-size:' . $TextSize . 'px;padding:1mm;' . $Textausrichtung . 'background-color:#' . $HintergrundFarbcode . ';color:#' . $TextFarbcodeLEER . '}
				</style>';

        $HTML = '<html>' . $HTML_CSS_Style;
        $HTML .= '<table class="bt">';

        if ($this->ReadPropertyBoolean("NamenAktorObjektCB") == true) {
            $TitelAR = array("Aktor");
        }
        if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
            if (isset($TitelAR)) {
                array_push($TitelAR, $ParentName1Tabelle);
            } else {
                $TitelAR = array($ParentName1Tabelle);
            }
        }
        if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {
            if (isset($TitelAR)) {
                array_push($TitelAR, $ParentName2Tabelle);
            } else {
                $TitelAR = array($ParentName2Tabelle);
            }
        }
        if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
            if (isset($TitelAR)) {
                array_push($TitelAR, $ParentName3Tabelle);
            } else {
                $TitelAR = array($ParentName3Tabelle);
            }
        }
        if (isset($TitelAR)) {
            array_push($TitelAR, "Hersteller", "ID", "Batterie", "Letztes Var-Update");
        } else {
            $TitelAR = array("Hersteller", "ID", "Batterie", "Letztes Var-Update");
        }

        $HTML .= '<tr>';
        $colspan = 1;
        foreach ($TitelAR as $TitelText) {
            $HTML .= '<th class="tb-title' . $this->InstanceID . '"><b>' . $TitelText . '</b></th>';
            $colspan++;
        }
        $HTML .= '</tr><tr>';

        if ($AlleLeer == "Alle") {
            if (isset($BatterienAR["Alle"])) {
                foreach ($BatterienAR["Alle"] as $Aktor) {
                    if ($this->ReadPropertyBoolean("NamenAktorObjektCB") == true) {
                        $HTML .= '<th class="tb-content' . $this->InstanceID . '">' . $Aktor["Name"] . '</th>';
                    }
                    if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                        $HTML .= '<th class="tb-content' . $this->InstanceID . '">' . $Aktor[$ParentName1Tabelle] . '</th>';
                    }
                    if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {
                        $HTML .= '<th class="tb-content' . $this->InstanceID . '">' . $Aktor[$ParentName2Tabelle] . '</th>';
                    }
                    if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                        $HTML .= '<th class="tb-content' . $this->InstanceID . '">' . $Aktor[$ParentName3Tabelle] . '</th>';
                    }
                    $HTML .= '<th class="tb-content' . $this->InstanceID . '">' . $Aktor["Hersteller"] . '</th><th class="tb-content' . $this->InstanceID . '">' . $Aktor["ID"] . '</th>';
                    if ($Aktor["Batterie"] == "OK") {
                        $HTML .= '<th class="tb-contentOK' . $this->InstanceID . '">' . $Aktor["Batterie"] . '</th>';
                    } elseif ($Aktor["Batterie"] == "LEER") {
                        $HTML .= '<th class="tb-contentLOW' . $this->InstanceID . '">' . $Aktor["Batterie"] . '</th>';
                    }
                    $HTML .= '<th class="tb-content' . $this->InstanceID . '">' . date("d.m.Y H:i", $Aktor["LetztesVarUpdateTimestamp"]) . 'Uhr</th></tr>';
                }
            } else {
                $HTML .= '<th class="tb-content' . $this->InstanceID . '" colspan="' . $colspan . '">Keine Aktoren mit Batterien gefunden!</th></tr>';
            }
            $HTML .= '</table></html>';
            $this->SetValueString("TabelleBatteryAlleVAR", $HTML);
        } elseif ($AlleLeer == "Leer") {
            if (isset($BatterienAR["Leer"])) {
                foreach ($BatterienAR["Leer"] as $Aktor) {
                    if ($this->ReadPropertyBoolean("NamenAktorObjektCB") == true) {
                        $HTML .= '<th class="tb-content' . $this->InstanceID . '">' . $Aktor["Name"] . '</th>';
                    }
                    if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                        $HTML .= '<th class="tb-content' . $this->InstanceID . '">' . $Aktor[$ParentName1Tabelle] . '</th>';
                    }
                    if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {
                        $HTML .= '<th class="tb-content' . $this->InstanceID . '">' . $Aktor[$ParentName2Tabelle] . '</th>';
                    }
                    if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                        $HTML .= '<th class="tb-content' . $this->InstanceID . '">' . $Aktor[$ParentName3Tabelle] . '</th>';
                    }
                    $HTML .= '<th class="tb-content' . $this->InstanceID . '">' . $Aktor["Hersteller"] . '</th><th class="tb-content' . $this->InstanceID . '">' . $Aktor["ID"] . '</th><th class="tb-contentLOW' . $this->InstanceID . '">' . $Aktor["Batterie"] . '</th><th class="tb-content' . $this->InstanceID . '">' . date("d.m.Y H:i", $Aktor["LetztesVarUpdateTimestamp"]) . 'Uhr</th></tr>';

                }
            } else {
                $HTML .= '<th class="tb-content' . $this->InstanceID . '" colspan="' . $colspan . '">Keine Aktoren mit leeren Batterien vorhanden!</th></tr>';
            }
            $HTML .= '</table></html>';
            $this->SetValueString("TabelleBatteryLowVAR", $HTML);
        }
    }

    private function Benachrichtigung($Batterien_AR)
    {
        if ($this->ReadPropertyInteger("BenachrichtigungsVar") != 0) {
            if (GetValueBoolean($this->ReadPropertyInteger("BenachrichtigungsVar")) === false) {
                return;
            }
        }
        $ParentName1Tabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
        $ParentName2Tabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
        $ParentName3Tabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));
        $BenachrichtigungsText = $this->ReadPropertyString("BatterieBenachrichtigungTEXT");

        foreach ($Batterien_AR as $Aktor) {
            $AktorName = $Aktor["Name"];
            if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                $AktorParent1Name = $Aktor[$ParentName1Tabelle];
            }
            if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {
                $AktorParent2Name = $Aktor[$ParentName2Tabelle];
            }
            if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                $AktorParent3Name = $Aktor[$ParentName3Tabelle];
            }
            $AktorHersteller = $Aktor["Hersteller"];
            $AktorID = $Aktor["ID"];
            $AktorBatterie = $Aktor["Batterie"];
            $AktorLetztesUpdateSEK = $Aktor["LetztesVarUpdateVorSek"];
            $AktorLetztesUpdateTS = date("d.m.Y H:i", $Aktor["LetztesVarUpdateTimestamp"]);

            //Code-W�rter austauschen gegen gew�nschte Werte
            $search = array("�AKTORHERSTELLER", "�AKTORID", "�AKTORBATTERIE", "�AKTORLETZTESUPDATE");
            $replace = array($AktorHersteller, $AktorID, $AktorBatterie, $AktorLetztesUpdateTS);

            if ($this->ReadPropertyBoolean("NamenAktorObjektCB") == true) {
                array_push($search, "�AKTORNAME");
                array_push($replace, $AktorName);
            }
            if ($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) {
                array_push($search, "�AKTORPARENT1");
                array_push($replace, $AktorParent1Name);
            }
            if ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) {
                array_push($search, "�AKTORPARENT2");
                array_push($replace, $AktorParent2Name);
            }
            if ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true) {
                array_push($search, "�AKTORPARENT3");
                array_push($replace, $AktorParent3Name);
            }
            $Text = str_replace($search, $replace, $BenachrichtigungsText);
            $Text = str_replace('�', '', $Text);

            //PUSH-NACHRICHT
            if ($this->ReadPropertyBoolean("PushMsgAktiv") == true) {
                $WFinstanzID = $this->ReadPropertyInteger("WebFrontInstanceID");
                if (($WFinstanzID != "") AND (@IPS_InstanceExists($WFinstanzID) === true)) {
                    if (strlen($Text) <= 256) {
                        WFC_PushNotification($WFinstanzID, "BatterieMonitor", $Text, "", 0);
                    } else {
                        IPS_LogMessage("BatterieMonitor", "FEHLER!!! - Die Textl�nge einer Push-Nachricht darf maximal 256 Zeichen betragen!!!");
                    }
                }
            }

            //EMAIL-NACHRICHT
            if ($this->ReadPropertyBoolean("EMailMsgAktiv") == true) {
                $SMTPinstanzID = $this->ReadPropertyInteger("SmtpInstanceID");
                if (($SMTPinstanzID != "") AND (@IPS_InstanceExists($SMTPinstanzID) === true)) {
                    SMTP_SendMail($SMTPinstanzID, "BatterieMonitor", $Text);
                }
            }

            //EIGENE-AKTION
            if ($this->ReadPropertyBoolean("EigenesSkriptAktiv") == true) {
                $SkriptID = $this->ReadPropertyInteger("EigenesSkriptID");
                if (($SkriptID != "") AND (@IPS_ScriptExists($SkriptID) === true)) {
                    if (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == false)) {
                        IPS_RunScriptEx($SkriptID, array("BMON_Name" => $AktorName, "BMON_ParentName1" => $AktorParent1Name, "BMON_Hersteller" => $AktorHersteller, "BMON_ID" => $AktorID, "BMON_Batterie" => $AktorBatterie, "BMON_Text" => $Text, "BMON_LetztesUpdateTS" => $AktorLetztesUpdateTS, "BMON_LetztesUpdateSEK" => $AktorLetztesUpdateSEK));
                    } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == false)) {
                        IPS_RunScriptEx($SkriptID, array("BMON_Name" => $AktorName, "BMON_ParentName2" => $AktorParent2Name, "BMON_Hersteller" => $AktorHersteller, "BMON_ID" => $AktorID, "BMON_Batterie" => $AktorBatterie, "BMON_Text" => $Text, "BMON_LetztesUpdateTS" => $AktorLetztesUpdateTS, "BMON_LetztesUpdateSEK" => $AktorLetztesUpdateSEK));
                    } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true)) {
                        IPS_RunScriptEx($SkriptID, array("BMON_Name" => $AktorName, "BMON_ParentName3" => $AktorParent3Name, "BMON_Hersteller" => $AktorHersteller, "BMON_ID" => $AktorID, "BMON_Batterie" => $AktorBatterie, "BMON_Text" => $Text, "BMON_LetztesUpdateTS" => $AktorLetztesUpdateTS, "BMON_LetztesUpdateSEK" => $AktorLetztesUpdateSEK));
                    } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true)) {
                        IPS_RunScriptEx($SkriptID, array("BMON_Name" => $AktorName, "BMON_ParentName1" => $AktorParent1Name, "BMON_ParentName2" => $AktorParent2Name, "BMON_ParentName3" => $AktorParent3Name, "BMON_Hersteller" => $AktorHersteller, "BMON_ID" => $AktorID, "BMON_Batterie" => $AktorBatterie, "BMON_Text" => $Text, "BMON_LetztesUpdateTS" => $AktorLetztesUpdateTS, "BMON_LetztesUpdateSEK" => $AktorLetztesUpdateSEK));
                    } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == false)) {
                        IPS_RunScriptEx($SkriptID, array("BMON_Name" => $AktorName, "BMON_ParentName1" => $AktorParent1Name, "BMON_ParentName2" => $AktorParent2Name, "BMON_Hersteller" => $AktorHersteller, "BMON_ID" => $AktorID, "BMON_Batterie" => $AktorBatterie, "BMON_Text" => $Text, "BMON_LetztesUpdateTS" => $AktorLetztesUpdateTS, "BMON_LetztesUpdateSEK" => $AktorLetztesUpdateSEK));
                    } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true)) {
                        IPS_RunScriptEx($SkriptID, array("BMON_Name" => $AktorName, "BMON_ParentName2" => $AktorParent2Name, "BMON_ParentName3" => $AktorParent3Name, "BMON_Hersteller" => $AktorHersteller, "BMON_ID" => $AktorID, "BMON_Batterie" => $AktorBatterie, "BMON_Text" => $Text, "BMON_LetztesUpdateTS" => $AktorLetztesUpdateTS, "BMON_LetztesUpdateSEK" => $AktorLetztesUpdateSEK));
                    } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true)) {
                        IPS_RunScriptEx($SkriptID, array("BMON_Name" => $AktorName, "BMON_ParentName1" => $AktorParent1Name, "BMON_ParentName3" => $AktorParent3Name, "BMON_Hersteller" => $AktorHersteller, "BMON_ID" => $AktorID, "BMON_Batterie" => $AktorBatterie, "BMON_Text" => $Text, "BMON_LetztesUpdateTS" => $AktorLetztesUpdateTS, "BMON_LetztesUpdateSEK" => $AktorLetztesUpdateSEK));
                    } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == false)) {
                        IPS_RunScriptEx($SkriptID, array("BMON_Name" => $AktorName, "BMON_Hersteller" => $AktorHersteller, "BMON_ID" => $AktorID, "BMON_Batterie" => $AktorBatterie, "BMON_Text" => $Text, "BMON_LetztesUpdateTS" => $AktorLetztesUpdateTS, "BMON_LetztesUpdateSEK" => $AktorLetztesUpdateSEK));
                    }
                }
            }
        }
    }

    public function BenachrichtigungsTest()
    {
        $ParentName1Tabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle1TB"));
        $ParentName2Tabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle2TB"));
        $ParentName3Tabelle = $this->Umlaute_Ersetzen($this->ReadPropertyString("NameParentTabelle3TB"));

        if (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == false)) {
            $TestAR[0] = array("Name" => "Test-Aktor", $ParentName1Tabelle => "1. OG", "Batterie" => "LEER", "Hersteller" => "HomeMatic", "ID" => "LEQ0123456", "LetztesVarUpdateTimestamp" => "1451169488", "LetztesVarUpdateVorSek" => "28800");
            $this->Benachrichtigung($TestAR);
        } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == false)) {
            $TestAR[0] = array("Name" => "Test-Aktor", $ParentName2Tabelle => "Wohnzimmer", "Batterie" => "LEER", "Hersteller" => "HomeMatic", "ID" => "LEQ0123456", "LetztesVarUpdateTimestamp" => "1451169488", "LetztesVarUpdateVorSek" => "28800");
            $this->Benachrichtigung($TestAR);
        } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true)) {
            $TestAR[0] = array("Name" => "Test-Aktor", $ParentName3Tabelle => "Hauptgeb�ude", "Batterie" => "LEER", "Hersteller" => "HomeMatic", "ID" => "LEQ0123456", "LetztesVarUpdateTimestamp" => "1451169488", "LetztesVarUpdateVorSek" => "28800");
            $this->Benachrichtigung($TestAR);
        } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true)) {
            $TestAR[0] = array("Name" => "Test-Aktor", $ParentName1Tabelle => "1. OG", $ParentName2Tabelle => "Wohnzimmer", $ParentName3Tabelle => "Hauptgeb�ude", "Batterie" => "LEER", "Hersteller" => "HomeMatic", "ID" => "LEQ0123456", "LetztesVarUpdateTimestamp" => "1451169488", "LetztesVarUpdateVorSek" => "28800");
            $this->Benachrichtigung($TestAR);
        } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == false)) {
            $TestAR[0] = array("Name" => "Test-Aktor", $ParentName1Tabelle => "1. OG", $ParentName2Tabelle => "Wohnzimmer", "Batterie" => "LEER", "Hersteller" => "HomeMatic", "ID" => "LEQ0123456", "LetztesVarUpdateTimestamp" => "1451169488", "LetztesVarUpdateVorSek" => "28800");
            $this->Benachrichtigung($TestAR);
        } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true)) {
            $TestAR[0] = array("Name" => "Test-Aktor", $ParentName2Tabelle => "Wohnzimmer", $ParentName3Tabelle => "Hauptgeb�ude", "Batterie" => "LEER", "Hersteller" => "HomeMatic", "ID" => "LEQ0123456", "LetztesVarUpdateTimestamp" => "1451169488", "LetztesVarUpdateVorSek" => "28800");
            $this->Benachrichtigung($TestAR);
        } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == true) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == true)) {
            $TestAR[0] = array("Name" => "Test-Aktor", $ParentName1Tabelle => "1. OG", $ParentName3Tabelle => "Hauptgeb�ude", "Batterie" => "LEER", "Hersteller" => "HomeMatic", "ID" => "LEQ0123456", "LetztesVarUpdateTimestamp" => "1451169488", "LetztesVarUpdateVorSek" => "28800");
            $this->Benachrichtigung($TestAR);
        } elseif (($this->ReadPropertyBoolean("NamenParentObjekt1CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt2CB") == false) AND ($this->ReadPropertyBoolean("NamenParentObjekt3CB") == false)) {
            $TestAR[0] = array("Name" => "Test-Aktor", "Batterie" => "LEER", "Hersteller" => "HomeMatic", "ID" => "LEQ0123456", "LetztesVarUpdateTimestamp" => "1451169488", "LetztesVarUpdateVorSek" => "28800");
            $this->Benachrichtigung($TestAR);
        }
    }

    private function ParentIDermitteln($ParentNr, $InstanzID)
    {
        switch ($this->ReadPropertyInteger($ParentNr)) {
            case 1:
                $ParentID = IPS_GetParent($InstanzID);
                break;
            case 2:
                $ParentID = IPS_GetParent(IPS_GetParent($InstanzID));
                break;
            case 3:
                $ParentID = IPS_GetParent(IPS_GetParent(IPS_GetParent($InstanzID)));
                break;
            case 4:
                $ParentID = IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent($InstanzID))));
                break;
            case 5:
                $ParentID = IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent($InstanzID)))));
                break;
            case 6:
                $ParentID = IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent($InstanzID))))));
                break;
            case 7:
                $ParentID = IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent($InstanzID)))))));
                break;
            case 8:
                $ParentID = IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent($InstanzID))))))));
                break;
            case 9:
                $ParentID = IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent($InstanzID)))))))));
                break;
            default:
                $ParentID = IPS_GetParent($InstanzID);
                break;
        }
        return $ParentID;
    }

    private function Umlaute_Ersetzen($String)
    {
        $Sonderzeichen = array("ö" => "�", "ü" => "�", "ß" => "�", "ä" => "�", "Ä" => "�", "Ü" => "�", "Ö" => "�", "é" => "�", "é" => "�");
        $StringNEU = strtr($String, $Sonderzeichen);
        return $StringNEU;
    }

    private function Array_UniqueBySubitem_Sort($array, $key, $sort_flags = SORT_STRING)
    {
        $items = array();
        foreach ($array as $index => $item) $items[$index] = $item[$key];
        $uniqueItems = array_unique($items, $sort_flags);
        return array_intersect_key($array, $uniqueItems);
    }

    private function SetValueBoolean($Ident, $Value)
    {
        $ID = $this->GetIDForIdent($Ident);
        if (GetValueBoolean($ID) <> $Value) {
            SetValueBoolean($ID, boolval($Value));
            return true;
        }
        return false;
    }

    private function SetValueInteger($Ident, $value)
    {
        $id = $this->GetIDForIdent($Ident);
        if (GetValueInteger($id) <> $value) {
            SetValueInteger($id, $value);
            return true;
        }
        return false;
    }

    private function SetValueString($Ident, $Value)
    {
        $ID = $this->GetIDForIdent($Ident);
        if (GetValueString($ID) <> $Value) {
            SetValueString($ID, strval($Value));
            return true;
        }
        return false;
    }

    protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
    {

        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 0);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != 0)
                throw new Exception("Variable profile type does not match for profile " . $Name);
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
    }

    protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations)
    {
        if (sizeof($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations) - 1][0];
        }

        $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);

        foreach ($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }

    }

    protected function RegisterTimer($Name, $Interval, $Script)
    {
        $id = @IPS_GetObjectIDByIdent($Name, $this->InstanceID);
        if ($id === false)
            $id = 0;


        if ($id > 0) {
            if (!IPS_EventExists($id))
                throw new Exception("Ident with name " . $Name . " is used for wrong object type", E_USER_WARNING);

            if (IPS_GetEvent($id)['EventType'] <> 1) {
                IPS_DeleteEvent($id);
                $id = 0;
            }
        }

        if ($id == 0) {
            $id = IPS_CreateEvent(1);
            IPS_SetParent($id, $this->InstanceID);
            IPS_SetIdent($id, $Name);
        }
        IPS_SetName($id, $Name);
        IPS_SetHidden($id, true);
        IPS_SetEventScript($id, $Script);
        if ($Interval > 0) {
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $Interval);

            IPS_SetEventActive($id, true);
        } else {
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);

            IPS_SetEventActive($id, false);
        }
    }

    protected function UnregisterTimer($Name)
    {
        $id = @IPS_GetObjectIDByIdent($Name, $this->InstanceID);
        if ($id > 0) {
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

        if ($Interval < 1) {
            if ($Event['EventActive'])
                IPS_SetEventActive($id, false);
        } else {
            if ($Event['CyclicTimeValue'] <> $Interval)
                IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $Interval);
            if (!$Event['EventActive'])
                IPS_SetEventActive($id, true);
        }
    }
}

?>