<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomologationsController extends Controller
{
    public function __construct(){
        $access = env("ACCESS_FILE");//conexion a access de sucursal
        if(file_exists($access)){
        try{  $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }
    }


    public function homologationClients(Request $request){
        $date = now()->format('Y-m-d');
        $fail =[];
        $conn = null;
        $ruta = "C:\Software DELSOL\FACTUSOL\Datos\FS\ ";
        $acbien = "C:\Users\MERS\Desktop\VPA2023.accdb";
        $years = [
            2016,
            2017,
            2018,
            2019,
            2020,
            2021,
            2022,
            2023
        ];
        $sucursales = [
            'SP1',
            'SP2',
            'SP3',
            'SPC',
            'SOT',
            'CR1',
            'CR2',
            'RA1',
            'RA2',
            'BOL',
            'BR1',
            'BR2',
            'AP1',
            'AP2',
            'PUE',
            'VPA',
            'PAN',
        ];
        foreach($years as $year){
            $clients = $request[$year];
            foreach($sucursales as $sucursal){
                $access = trim($ruta).$sucursal.$year.'.accdb'; 
                $arch = $sucursal.$year;
                if(file_exists($access)){
                    try{
                    $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
                    $cliat = "SELECT TOP 1 * FROM F_CLI";
                    $exec = $this->conn->prepare($cliat);
                    $exec -> execute();
                    $jej = $exec->fetch(\PDO::FETCH_ASSOC);
                    $columns = implode(",",array_keys($jej));
                     
                    $deletecli = "DELETE FROM F_CLI";
                    $exec = $this->conn->prepare($deletecli);
                    $exec -> execute();

                    //conexcion para pasar clientes
                    $this->con  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$acbien."; Uid=; Pwd=;");
                    $select = "SELECT $columns FROM F_CLI";
                    $exec = $this->con->prepare($select);
                    $exec -> execute();
                    $fil = $exec->fetchall(\PDO::FETCH_ASSOC);

                    foreach($fil as $row){
                        $column = array_keys($row);
                        $values = array_values($row);
                        $impcol = implode(",",$column);
                        $signos = implode(",",array_fill(0, count($column),'?'));
                        //insercion para la nueva clientes 
                        $insert = "INSERT INTO F_CLI ($impcol) VALUES($signos)";
                        $exec = $this->conn->prepare($insert);
                        $exec -> execute($values);
                    }


                    foreach($clients as $client){
                        $original = substr($client['original'],0,-1);
                        $remplazo = $client['remplazo'];
                        $updatecli = substr($original,0,strpos($original,','));
                        $delcli = substr(substr($original,strpos($original,',')),1);




                        $upalb = "UPDATE F_ALB SET CLIALB = $remplazo , FUMALB = "."'".$date."'"." WHERE CLIALB IN (".$original.") AND FUMALB < #".$date."# ";
                        $exec = $this->conn->prepare($upalb);
                        $exec -> execute();

                        $upfab = "UPDATE F_FAB SET CLIFAB = $remplazo , FUMFAB = "."'".$date."'"." WHERE CLIFAB IN (".$original.") AND FUMFAB < #".$date."# ";
                        $exec = $this->conn->prepare($upfab);
                        $exec -> execute();

                        $upfac = "UPDATE F_FAC SET CLIFAC = $remplazo , FUMFAC = "."'".$date."'"." WHERE CLIFAC IN (".$original.") AND FUMFAC < #".$date."# ";
                        $exec = $this->conn->prepare($upfac);
                        $exec -> execute();
                        
                        $uppcl = "UPDATE F_PCL SET CLIPCL = $remplazo , FUMPCL = "."'".$date."'"." WHERE CLIPCL IN (".$original.") AND FUMPCL < #".$date."# ";
                        $exec = $this->conn->prepare($uppcl);
                        $exec -> execute();

                        $uppre = "UPDATE F_PRE SET CLIPRE = $remplazo , FUMPRE = "."'".$date."'"." WHERE CLIPRE IN (".$original.") AND FUMPRE < #".$date."# ";
                        $exec = $this->conn->prepare($uppre);
                        $exec -> execute();

                        
                        $sfdk = "realizado";
                    }

                    // $upantici = "UPDATE F_ANT SET TPVIDANT = "."";
                    // $exec = $this->conn->prepare($upantici);
                    // $exec -> execute();

                    }catch(\PDOException $e){ die($e->getMessage()); }

                }else{
                    $fail[]=$arch;
                }
            }
        }

        $res = [
            "fail"=>$fail,
            "goal"=>$sfdk
        ];

        return response()->json($res);

    }

    public function products(Request $request){
        $date = now()->format('Y-m-d');
        $products = $request->all();
        $yes = [];
        $response = [];
        $fail =[];
        $conn = null;
        $ruta = "C:\Software DELSOL\FACTUSOL\Datos\FS\ ";
        $years = [
            2016,
            2017,
            2018,
            2019,
            2020,
            2021,
            2022,
            2023
        ];

        $sucursales = [
            'SP1',
            // 'SP2',
            // 'SP3',
            // 'SPC',
            // 'SOT',
            // 'CR1',
            // 'CR2',
            // 'RA1',
            // 'RA2',
            // 'BOL',
            // 'BR1',
            // 'BR2',
            // 'AP1',
            // 'AP2',
            // 'PUE',
            // 'VPA',
            // 'PAN',
        ];
        foreach($years as $year){
            foreach($sucursales as $sucursal){
                $access = trim($ruta).$sucursal.$year.'.accdb'; 
                $arch = $sucursal.$year;
                if(file_exists($access)){
                    try{
                        $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
                        
                        foreach($products as $product){
                            $original = "'".$product['original']."'";
                            $remplazo = "'".$product['remplazo']."'";


                            $updatefac = "UPDATE F_LFA SET ARTLFA = ".$remplazo." WHERE ARTLFA = ".$original;//facturas
                            $exec = $this->conn->prepare($updatefac);
                            $exec -> execute();
                            $updatealb = "UPDATE F_LAL SET ARTLAL = ".$remplazo." WHERE ARTLAL = ".$original;//albaranes
                            $exec = $this->conn->prepare($updatealb);
                            $exec -> execute();
                            $updatelen = "UPDATE F_LEN SET ARTLEN = ".$remplazo." WHERE ARTLEN = ".$original;//entradas
                            $exec = $this->conn->prepare($updatelen);
                            $exec -> execute();
                            $updatefre = "UPDATE F_LFR SET ARTLFR = ".$remplazo." WHERE ARTLFR = ".$original;//facturas recibidas
                            $exec = $this->conn->prepare($updatefre);
                            $exec -> execute();
                            $updatedev = "UPDATE F_LFD SET ARTLFD = ".$remplazo." WHERE ARTLFD = ".$original;//devoluciones
                            $exec = $this->conn->prepare($updatedev);
                            $exec -> execute();
                            $updatetra = "UPDATE F_LTR SET ARTLTR = ".$remplazo." WHERE ARTLTR = ".$original;//traspasos
                            $exec = $this->conn->prepare($updatetra);
                            $exec -> execute();
                            $updateab = "UPDATE F_LFB SET ARTLFB = ".$remplazo." WHERE ARTLFB = ".$original;//abonos
                            $exec = $this->conn->prepare($updateab);
                            $exec -> execute();
                            $updatesai = "UPDATE F_LSA SET ARTLSA = ".$remplazo." WHERE ARTLSA = ".$original;//salidas internas
                            $exec = $this->conn->prepare($updatesai);
                            $exec -> execute();
                            $updatecom = "UPDATE F_LFC SET ARTLFC = ".$remplazo." WHERE ARTLFC = ".$original;//articulos compuestos
                            $exec = $this->conn->prepare($updatecom);
                            $exec -> execute();

                        }
                    }catch(\PDOException $e){ die($e->getMessage()); }
                }else{
                    $fail[]=$arch;
                }
            } 
        }
        return response()->json($fail);
    }
}
