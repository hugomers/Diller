<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    public function __construct(){
        $access = env("ACCESS_FILE");//conexion a access de cedis
        if(file_exists($access)){
        try{  $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }
    }

    public function index(){
        $vizapp = [
            "articulos"=>[],
            "faltantes"=>[]
        ];
        $cedis = ["articulos"=>[]];
         
        $failstor = []; 
        $stor = [];

        $mspro = DB::table('products')->where('_state','!=',4)->get();
        $vizapp['articulos']=count($mspro);
        foreach($mspro as $proms){
            $arti[]=$proms->code;
        }

        $cedpro = "SELECT CODART FROM F_ART";
        $exec = $this->conn->prepare($cedpro);
        $exec -> execute();
        $fact=$exec->fetchall(\PDO::FETCH_ASSOC);
        $cedis['articulos']=count($fact);
        foreach($fact as $proced){
            $codi[]=$proced['CODART'];
        }
        $dife = array_diff($codi,$arti);
        $diferencia = array_values($dife);
        $vizapp['faltantes']=$diferencia;

        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type',1)->get();
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["articulos" => $codi]);//se codifica el arreglo de los clientes
            //inicio de opciones de curl
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstor[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
                // $failstor[] =["sucursal"=>$store->alias, "mssg"=>$exec];//la sucursal se almacena en sucursales fallidas
            }else{
                $stor [] = ["sucursal"=>$store->alias, "mssg"=>$exc];//de lo contrario se almacenan en sucursales
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales


        $res = [
            "cedis"=>$cedis,
            "vizapp"=>$vizapp,
            "sucursales"=>[
                "good"=>$stor,
                "fail"=>$failstor
            ]
        ];

        return $res;


    }

    public function pairingProducts(){
        $failstor = [];
        $stor = [];
        $goals= [
            "articulos"=>[
                "actualizados"=>[],
                "insertados"=>[]
            ],
            "precios"=>[]
        ];
        $fails=[];

        $proced = "SELECT CODART FROM F_ART";//se obtienen los articulos actualuales de cedis
        $exec = $this->conn->prepare($proced);
        $exec -> execute();
        $fil=$exec->fetchall(\PDO::FETCH_ASSOC);
        $colsTab = array_keys($fil[0]);//llaves de el arreglo 
        foreach($fil as $row){
            foreach($colsTab as $col){ $row[$col] = utf8_encode($row[$col]); }
            $codigo[]="'".$row['CODART']."'";//se botienen ids de cedis
            $mysqlcod[]=$row['CODART'];//se bbotienen ids de cedis
        }

        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 1)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/pairing";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["pro" => $codigo]);//se codifica el arreglo de los proveedores
            //inicio de opciones de curl
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstor[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
                // $failstor[] =["sucursal"=>$store->alias, "mssg"=>$exec];//la sucursal se almacena en sucursales fallidas
            }else{
                $stor [] = ["sucursal"=>$store->alias, "mssg"=>$exc];//de lo contrario se almacenan en sucursales
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales

        DB::statement("SET SQL_SAFE_UPDATES = 0;");//se desactiva safe update
        DB::statement("SET FOREIGN_KEY_CHECKS = 0;");//se desactivan las llaves foraneas
        DB::table('product_stock AS PS')->join('products as P','P.id','PS._product')->whereNotIn('P.code',$mysqlcod)->delete();//se vacia la tabla de stocks que no existan en factusol
        DB::table('product_prices as PP')->join('products as P','P.id','PP._product')->whereNotIn('P.code',$mysqlcod)->delete();//se vacia la tabla de precios que no existan en factusol
        DB::table('products')->whereNotIn('code',$mysqlcod)->update(['_state'=>4]);//productos que no estan en 
        DB::statement("SET SQL_SAFE_UPDATES = 1;");//se activan las llaves foraneas
        DB::statement("SET FOREIGN_KEY_CHECKS = 1;");//se activa safe update

        $mysqlpro = DB::table('products')->where('_state','!=',4)->get();
        foreach($mysqlpro as $pros){
            $codms[]="'".$pros->code."'";
        }

        $dife = array_diff($codigo,$codms);
        $faltantes = array_values($dife);

        if(count($faltantes) > 0){
        
            $prosu = "SELECT * FROM F_ART WHERE CODART IN (".implode(",",$faltantes).")";
            $exec = $this->conn->prepare($prosu);
            $exec -> execute();
            $msfil=$exec->fetchall(\PDO::FETCH_ASSOC);
            $colsTab = array_keys($msfil[0]);//llaves de el arreglo 
            foreach($msfil as $msnw){
                foreach($colsTab as $col){ $msnw[$col] = utf8_encode($msnw[$col]); }
                $productms = DB::table('products')->where('code',$msnw['CODART'])->value('id');
                if($productms){
                    $updatems = DB::table('products')->where('id',$productms)->update(['_state'=>1]);
                    $goals['articulos']['actualizados'][]= "se actualizo el modelo ".$msnw['CODART'];
                }else{
                    $caty = DB::table('product_categories as PC')// SE BUSCA LA CATEGORIA DE EL PRODUCTO EN MYSQL
                    ->join('product_categories as PF', 'PF.id', '=','PC.root')
                    ->where('PC.alias', $msnw['CP1ART'])
                    ->where('PF.alias', $msnw['FAMART'])
                    ->value('PC.id'); 
                    if($caty){//debe de existir la categoria
                        $provider = DB::table('providers')->where('fs_id',$msnw['PHAART'])->value('id');
                        $status = $msnw['NPUART'] == 0 ? 1 : 3; //SE CAMBIA EL STATUS EN MYSQL PARA INSERTARLO
                        $unit_assort = DB::table('units_measures')->where('name',$msnw['CP3ART'])->value('id');
                        $ptosm = [
                        "id"=>null,
                        "short_code"=>INTVAL($msnw['CCOART']),
                        "code" =>$msnw['CODART'],
                        "barcode" =>$msnw['EANART'],
                        "description"=>$msnw['DESART'],
                        "label"=>$msnw['DEEART'],
                        "reference" =>$msnw['REFART'],
                        "pieces" =>INTVAL($msnw['UPPART']),
                        "cost"=>$msnw['PCOART'],
                        "created_at" =>$msnw['FALART'],
                        "updated_at" =>$msnw['FUMART'],
                        "default_amount" =>1,
                        "_kit" =>NULL,
                        "picture" =>NULL,
                        "_provider" =>$provider,
                        "_category" =>INTVAL($caty),
                        "_maker" =>INTVAL($msnw['FTEART']),
                        "_unit_mesure" =>INTVAL($msnw['UMEART']),
                        "_state" =>INTVAL($status),
                        "_product_additional" =>NULL,
                        "_assortment_unit"=>$unit_assort
                        ];
                        try{//SE MUESTRA LOS ARTICULO QUE SE INSERTARAN                     
                            $ptosm["created_at"]=now();
                            $ptosm["updated_at"]=now();
                            $insproduct = DB::table('products')->insert($ptosm);
                            $priced = "SELECT * FROM F_LTA WHERE ARTLTA = ".$msnw['CODART'].")";
                            $exec = $this->conn->prepare($priced);
                            $exec -> execute();
                            $prices=$exec->fetchall(\PDO::FETCH_ASSOC);
                            foreach($prices as $price){
                                $precio = $price['PRELTA'] == null ? 0 : $price['PRELTA'];
                                $idmysql = DB::table('products')->where('code',$price['ARTLTA'])->value('id');
                                $pricems = [
                                    "_rate"=>INTVAL($price['TARLTA']),
                                    "_type"=>1,
                                    "_product"=>$idmysql,
                                    "price"=>DOUBLEVAL($precio),
                                ];
                                $insertpre =  DB::table('product_prices')->insert($pricems);
                                if($insertpre){
                                    $goals['precios'][]="precios de el modelo ".$price['ARTLTA']." insertado";
                                }else{$fails[]="hubo problema con el articulo ".$price['ARTLTA'];}
                            }
                        }catch (\Exception $e) {$fails[]= $e ->getMessage();}
                        $goals['articulos']['insertados'][]="articulo ".$msnw['CODART']." insertado correctamente";                                                                                                                                                                                   
                    }else{$fails[] = "{$msnw['CODART']}: La categoria {$msnw['CP1ART']} de la familia {$msnw['FAMART']}, no se encuentra en VizApp";}//EN CASO DE NO TENER LA CATEGORIA CORRECTA MANDARA UNA ALERTA
            }}
        }else{$goals['articulos']="Los Articulos estan bien";}

        $res =[
            "sucursales"=>[
                "goals"=>$stor,
                "fails"=>$failstor
            ],
            "mysql"=>[
                "fails"=>$fails,
                "goals"=>$goals
            ]
        ];
        return response()->json($res,200);
    }

    public function missingProducts(Request $request){
        $sucpro = $request->products;
        if($sucpro){
        $proced = "SELECT CODART,EANART,FAMART,DESART,DEEART,DETART,DLAART,EQUART,CCOART,PHAART,REFART,FTEART,PCOART,FALART,FUMART,UPPART,CANART,CAEART,UMEART,CP1ART,CP2ART,CP3ART,CP4ART,CP5ART,MPTART,UEQART FROM F_ART WHERE CODART IN (".implode(",",$sucpro).")";
        $exec = $this->conn->prepare($proced);
        $exec -> execute();
        $fil=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($fil){
        $colsTab = array_keys($fil[0]);//llaves de el arreglo 
        foreach($fil as $row){
            foreach($colsTab as $col){ $row[$col] = utf8_encode($row[$col]); }
            $ids[]="'".$row['CODART']."'";
            $codigo[]=$row;
        }
        $priced = "SELECT * FROM F_LTA WHERE TARLTA NOT IN (7) AND  ARTLTA IN (".implode(",",$ids).")";
        $exec = $this->conn->prepare($priced);
        $exec -> execute();
        $prices=$exec->fetchall(\PDO::FETCH_ASSOC);
        foreach($prices as $price){
            $pri[]=$price;
        }
        return response()->json(["articulos"=>$codigo,
                                "precios"=>$pri]);
        }else{return response()->json(null);}
        } else{return response()->json(null);}  
    }

    public function replaceProducts(Request $request){
        $mysql = [];
        $factusol = [];
        $stor = [];
        $failstore = [];

        $products = $request->all();
        foreach($products as $product){
            $rows[]=$product;
            $original = "'".$product['original']."'";
            $replace = "'".$product['replace']."'";
            //remplazos mysql
            $idoriginal = DB::table('products')->where('code',$product['original'])->value('id');
            $idreplace = DB::table('products')->where('code',$product['replace'])->value('id');

            $reppro = DB::table('products')->where('id',$idoriginal)->update(['_state'=>4]);
            if($reppro){$mysql[]=$product['original']." se actualizo correctamente";}else{$mysql[]=$product['original']." problema de actualizacion";}
            $repsal = DB::table('sale_bodies')->where('_product',$idoriginal)->update(['_product'=>$idreplace]);
            if($repsal){$mysql[]=$product['original']."productos remplazado en ventas por ".$product['replace'];}else{$mysql[]=$product['original']." error al remplazar ventas";}
            $reppur = DB::table('purchase_bodies')->where('_product',$idoriginal)->update(['_product'=>$idreplace]);
            if($reppur){$mysql[]=$product['original']."productos remplazado en compras por ".$product['replace'];}else{$mysql[]=$product['original']." error al remplazar compras";}
            $repreq = DB::table('requisition_bodies')->where('_product',$idoriginal)->update(['_product'=>$idreplace]);
            if($repreq){$mysql[]=$product['original']."productos remplazado en requisition por ".$product['replace'];}else{$mysql[]=$product['original']." error al remplazar requisition";}
            $repord = DB::table('order_bodies')->where('_product',$idoriginal)->update(['_product'=>$idreplace]);
            if($repord){$mysql[]=$product['original']."productos remplazado en order por ".$product['replace'];}else{$mysql[]=$product['original']." error al remplazar order";}
            $reptra =DB::table('transfer_bw_bodies')->where('_product',$idoriginal)->update(['_product'=>$idreplace]);
            if($reptra){$mysql[]=$product['original']."productos remplazado en traspasos por almacen por ".$product['replace'];}else{$mysql[]=$product['original']." error al remplazar traspasos por almacen";}
            $rept = DB::table('transfer_bb_bodies')->where('_product',$idoriginal)->update(['_product'=>$idreplace]);
            if($rept){$mysql[]=$product['original']."productos remplazado en traspasos por sucursal por ".$product['replace'];}else{$mysql[]=$product['original']." error al remplazar traspasos por sucursal";}
            DB::statement("SET SQL_SAFE_UPDATES = 0;");//se desactiva safe update
            DB::statement("SET FOREIGN_KEY_CHECKS = 0;");//se desactivan las llaves foraneas
            $deletesto = DB::table('product_stock')->where('_product',$idoriginal);//se vacia la tabla de proveedores
            if($deletesto){$mysql[]=$product['original']." producto eliminado en stock";}else{$mysql[]=$product['original']." error al eliminar en stock";}
            $deleteprice = DB::table('product_prices')->where('_product',$idoriginal);//se vacia la tabla de proveedores
            if($deleteprice){$mysql[]=$product['original']." producto eliminado en precios";}else{$mysql[]=$product['original']." error al eliminar en precios";}
            DB::statement("SET SQL_SAFE_UPDATES = 1;");//se activan las llaves foraneas
            DB::statement("SET FOREIGN_KEY_CHECKS = 1;");//se activa safe update
            try{
                $upda = "UPDATE F_LFA SET ARTLFA = $replace WHERE ARTLFA = $original";
                $exec = $this->conn->prepare($upda);
                $exec -> execute();
                if($exec){$factusol[]=$product['original']." articulos remplazado en facturas por ".$product['replace'];}else{$factusol[]=$product['original']." error en remplazar en facturas";}
                $updsto = "UPDATE F_LFR SET ARTLFR = $replace WHERE ARTLFR = $original";
                $exec = $this->conn->prepare($updsto);
                $exec -> execute();
                if($exec){$factusol[]=$product['original']." articulos remplazado en facturas recibidas por ".$product['replace'];}else{$factusol[]=$product['original']." error en remplazar en facturas recibidas";}
                $updlta = "UPDATE F_LEN SET ARTLEN = $replace WHERE ARTLEN = $original";
                $exec = $this->conn->prepare($updlta);
                $exec -> execute();
                if($exec){$factusol[]=$product['original']." articulos remplazado en entradas por ".$product['replace'];}else{$factusol[]=$product['original']." error en remplazar en entradas";}
                $updltr = "UPDATE F_LTR SET ARTLTR = $replace WHERE ARTLTR = $original";
                $exec = $this->conn->prepare($updltr);
                $exec -> execute();
                if($exec){$factusol[]=$product['original']." articulos remplazado en traspasos por ".$product['replace'];}else{$factusol[]=$product['original']." error en remplazar en traspasos";}
                $updcin = "UPDATE F_LFB SET ARTLFB = $replace WHERE ARTLFB = $original";
                $exec = $this->conn->prepare($updcin);
                $exec -> execute();
                if($exec){$factusol[]=$product['original']." articulos remplazado en abonos por ".$product['replace'];}else{$factusol[]=$product['original']." error en remplazar en abonos";}
                $upddev = "UPDATE F_LFD SET ARTLFD = $replace WHERE ARTLFD = $original";
                $exec = $this->conn->prepare($upddev);
                $exec -> execute();
                if($exec){$factusol[]=$product['original']." articulos remplazado en devoluciones por ".$product['replace'];}else{$factusol[]=$product['original']." error en remplazar en devoluciones";}
                $deleteart = "DELETE FROM F_ART WHERE CODART = $original";
                $exec = $this->conn->prepare($deleteart);
                $exec -> execute();
                if($exec){$factusol[]=$product['original']." eliminado en Articulos";}else{$factusol[]=$product['original']." error sl eliminar en Articulos";}
                $deletetar = "DELETE FROM F_LTA WHERE ARTLTA = $original";
                $exec = $this->conn->prepare($deletetar);
                $exec -> execute();
                if($exec){$factusol[]=$product['original']." eliminado en Precios";}else{$factusol[]=$product['original']." error sl eliminar en Precios";}
                $deletesto = "DELETE FROM F_STO WHERE ARTSTO = $original";
                $exec = $this->conn->prepare($deletesto);
                $exec -> execute();
                if($exec){$factusol[]=$product['original']." eliminado en Stock";}else{$factusol[]=$product['original']." error sl eliminar en Stock";}
                $deleteean = "DELETE FROM F_EAN WHERE ARTEAN = $original";
                $exec = $this->conn->prepare($deleteean);
                $exec -> execute();
                if($exec){$factusol[]=$product['original']." eliminado en Familiarizados";}else{$factusol[]=$product['original']." error sl eliminar en Familiarizados";}
        
              }catch (\PDOException $e){ die($e->getMessage());}
        }
        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/replaceProducts";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["product" => $rows]);//se codifica el arreglo de los proveedores
            //inicio de opciones de curl
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstores[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
            }else{
                // $stor[] = $store->alias." cambios hechos";//de lo contrario se almacenan en sucursales
                $stor[] =["sucursal"=>$store->alias, "mssg"=>$exc];
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
        $res = [
            "cedis"=>$factusol,
            "mysql"=>$mysql,
            "sucursales"=>[
            "gols"=>$stor,
            "fails"=>$failstores
            ]
        ];
        return response()->json($res);


    }

    public function highProducts(Request $request){
        $insertados=[];
        $actualizados=[];
        $fail=[
            "categoria"=>[],
            "codigo_barras"=>[],
            "codigo_corto"=>[], 
        ];
        $stor=[];
        $failstores=[];
        $mysql=[
            "insertados"=>[],
            "actualizados"=>[],
            "fail"=>[
                "insertados"=>[],
                "actualizados"=>[]
            ]
        ];
        $almacenes ="SELECT CODALM FROM F_ALM";
        $exec = $this->conn->prepare($almacenes);
        $exec -> execute();
        $fil=$exec->fetchall(\PDO::FETCH_ASSOC);

        $tari ="SELECT CODTAR FROM F_TAR";
        $exec = $this->conn->prepare($tari);
        $exec -> execute();
        $filtar=$exec->fetchall(\PDO::FETCH_ASSOC);

        $articulos= $request->all();
        
        $dato = $articulos;
        foreach($articulos as $art){ 
            
            
            
            $codigo = trim($art["CODIGO"]);
            $deslarga = trim($art["DESCRIPCION"]);
            $desgen = trim(substr($art["DESCRIPCION"],0,50));
            $deset = trim(substr($art["DESCRIPCION"],0,30));
            $destic = trim(substr($art["DESCRIPCION"],0,20));
            $famart = trim($art["FAMILIA"]);
            $cat = trim($art["CATEGORIA"]);
            $date_format = date("d/m/Y");
            // $barcode = trim($art["CB"]);
            if(isset($art["CB"])){$barcode = trim($art["CB"]);}else{$barcode = null;}
            // $cost = $art["COSTO"];
            if(isset($art["COSTO"])){$cost = $art["COSTO"];}else{$cost = 0;}
            // $medidas = trim($art["MEDIDAS NAV"]);
            if(isset($art["MEDIDAS NAV"])){$medidas = trim($art["MEDIDAS NAV"]);}else{$medidas = null;}
            // $luces = trim($art["#LUCES"]);
            if(isset($art["#LUCES"])){$luces = trim($art["#LUCES"]);}else{$luces = null;}
            $PXC = trim($art["PXC"]);
            $refart = trim($art["REFERENCIA"]);
            $cp3art = trim($art["UNIDA MED COMPRA"]);

            $codbar = $barcode == null ? "'"."'" : $barcode;
            $luz = $luces == null ? "'"."'" : $luces;
            $med = $medidas == null ? "'"."'" : $medidas;

            // return response()->json($barcode);

            $articulo  = [              
                $codigo,
                $codbar,
                $famart,
                $desgen,
                $deset,
                $destic,
                $deslarga,
                $art["PXC"],
                $art["CODIGO CORTO"],
                $art["PROVEEDOR"],
                $refart,
                $art["FABRICANTE"],
                $cost,
                $date_format,
                $date_format,
                $art["PXC"],
                1,
                1,
                1,
                $cat,
                $luz,
                $cp3art,
                $art["PRO RES"],
                $med,
                0,
                "Peso"
            ];



            $caty = DB::table('product_categories as PC')->join('product_categories as PF', 'PF.id', '=','PC.root')->where('PC.alias', $cat)->where('PF.alias', $famart)->value('PC.id');
            if($caty){
                //mysql
                $assortmen = DB::table('units_measures')->where('name',$cp3art)->value('id');
                $provider = DB::table('providers')->where('fs_id',$art["PROVEEDOR"])->value('id');
                $insms = [
                    "code"=>$codigo,
                    "short_code"=>$art["CODIGO CORTO"],
                    "barcode"=>$barcode,
                    "description"=>$deslarga,
                    "label"=>$deset,
                    "reference"=>$refart,
                    "pieces"=>$PXC,
                    "cost"=>$cost,
                    "created_at"=>now(),
                    "updated_at"=>now(),
                    "default_amount"=>1,
                    "_kit"=>null,
                    "picture"=>null,
                    "_provider"=>$provider,
                    "_category"=>$caty,
                    "_maker"=>$art["FABRICANTE"],
                    "_unit_mesure"=>1,
                    "_state"=>1,
                    "_product_additional"=>null,
                    "_assortment_unit"=>$assortmen
                ];
                $pupdms = DB::table('products')->where('code',$codigo)->first();
                if($pupdms){
                    $updtms = DB::table('products')->where('code',$codigo)->update(['_category'=>$caty,'updated_at'=>now(),'barcode'=>$barcode,'cost'=>$cost,'pieces'=>$PXC,'reference'=>$refart,'_assortment_unit'=>$assortmen]);
                    if($updtms){$mysql['actualizados'][]="Se actualizo el modelo ".$codigo." con exito";}else{$mysql['fail']['actualizados'][]="hubo problemas al actualizar el modelo ".$codigo;}
                }else{
                    $shor = DB::table('products')->where('short_code',$art["CODIGO CORTO"])->first();
                    if($shor){$mysql['fail']['insertados']="El codigo corto ".$art["CODIGO CORTO"]." a sido otorgado a el codigo ".$shor->code."no se puede duplicar";}else{
                    $insmysql = DB::table('products')->insert($insms);
                    if($insmysql){$mysql['insertados'][]="Se inserto correctamente el modelo ".$codigo;}else{$mysql['fail']['insertados'][]="el modelo ".$codigo." tuvo un error al insertar";}
                    }
                }
                
                //cedis
                $sqlart = "SELECT CODART, EANART FROM F_ART WHERE CODART = ?";
                $exec = $this->conn->prepare($sqlart);
                $exec -> execute([$codigo]);
                $arti=$exec->fetch(\PDO::FETCH_ASSOC);


     
                if($arti){
                    $update = "UPDATE F_ART SET FAMART = "."'".$famart."'"." , CP1ART = "."'".$cat."'"."  , FUMART = "."'".$date_format."'".", EANART = ".$codbar.", PCOART = ".$cost.", UPPART = ".$PXC." , EQUART = ".$PXC.", REFART = "."'".$refart."'"."  , CP3ART = "."'".$cp3art."'"."  WHERE CODART = ? "; 
                    $exec = $this->conn->prepare($update);
                    $exec -> execute([$codigo]);
                    $actualizados[]="Se actualizo el modelo  ".$codigo." con codigo de barras ".$barcode;
                }else{
                    if($barcode != null){
                    $codigob = "SELECT CODART, EANART FROM F_ART WHERE EANART = "."'".$barcode."'";
                    $exec = $this->conn->prepare($codigob);
                    $exec -> execute();
                    $barras=$exec->fetch(\PDO::FETCH_ASSOC);
                    
                    if($barras){
                        $fail['codigo_barras'][]="El codigo de barras ".$barcode." esta otorgado a el articulo ".$barras['CODART']." no se pueden duplicar";}
                    }
                        
                        $codigoc = "SELECT CODART, CCOART FROM F_ART WHERE CCOART = ".$art["CODIGO CORTO"];
                        $exec = $this->conn->prepare($codigoc);
                        $exec -> execute();
                        $corto=$exec->fetch(\PDO::FETCH_ASSOC);
                        
                    
                        if($corto){
                            $fail['codigo_corto'][]="El codigo corto ".$art["CODIGO CORTO"]." esta otorgado al articulo ".$corto['CODART']." no se pueden duplicar";
                        }else{
                            $insert = "INSERT INTO F_ART (CODART,EANART,FAMART,DESART,DEEART,DETART,DLAART,EQUART,CCOART,PHAART,REFART,FTEART,PCOART,FALART,FUMART,UPPART,CANART,CAEART,UMEART,CP1ART,CP2ART,CP3ART,CP4ART,CP5ART,MPTART,UEQART) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            $exec = $this->conn->prepare($insert);
                            $exec -> execute($articulo);
                            foreach($fil as $row){
                                $alm=$row['CODALM'];
                                $insertsto = "INSERT INTO F_STO (ARTSTO,ALMSTO,MINSTO,MAXSTO,ACTSTO,DISSTO) VALUES (?,?,?,?,?,?) ";
                                $exec = $this->conn->prepare($insertsto);
                                $exec -> execute([$codigo,$alm,0,0,0,0]);
                            }
                            foreach($filtar as $tar){
                                $price =$tar['CODTAR'];
                                $insertlta = "INSERT INTO F_LTA (TARLTA,ARTLTA,MARLTA,PRELTA) VALUES (?,?,?,?) ";
                                $exec = $this->conn->prepare($insertlta);
                                $exec -> execute([$price,$codigo,0,0]);
                            }
                            $insertados[]="Se inserto el codigo ".$codigo."con exito";
                        }
                } 
            }else{$fail['categoria'][]="no existe la categoria ".$cat." de la familia ".$famart." de el producto ".$codigo;}    
        }


        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 1)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/highProducts";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["product" => $dato]);//se codifica el arreglo de los proveedores
            //inicio de opciones de curl
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstores[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
            }else{
                // $stor[] = $store->alias." cambios hechos";//de lo contrario se almacenan en sucursales
                $stor[] =["sucursal"=>$store->alias, "mssg"=>$exc];
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales


        $res = [
            "cedis"=>[
                "insertados"=>$insertados,
                "actuazliados"=>$actualizados,
                "fails"=>$fail
            ],
            "mysql"=>$mysql,
            "sucursales"=>[
                "fail"=>$failstores,
                "goals"=>$stor
            ]
        ];
        return response()->json($res);

    }

    public function highPrices(Request $request){
        $priceslocal = [];
        $pricesforeign = [];
        $fail = [];
        $prices = $request->all();
        $margin = 1.05;
        $oferta = 0;
        $linea= 0;
        foreach($prices as $price){
            $articulo = $price['MODELO'];
            $cosfs = "SELECT PCOART FROM F_ART WHERE CODART = ?";
            $exec = $this->conn->prepare($cosfs);
            $exec -> execute([$articulo]);
            $cossis=$exec->fetch(\PDO::FETCH_ASSOC);
            if($cossis){
                //preparando request
                if(isset($price["COSTO"])){$costo = round($price["COSTO"],2);}else{$costo = intval($cossis['PCOART']);}
                if(isset($price["AAA"])){$aaa = round($price["AAA"],2);}else{$aaa = intval($cossis['PCOART']);}
                $centro = round($price["CENTRO"],0);
                $especial = round($price["ESPECIAL"],0);
                $caja = round($price["CAJA"],0);
                $docena = round($price["DOCENA"],0);
                $mayoreo = round($price["MAYOREO"],0);
                //calculando menudeo
                if(isset($price["MENUDEO"])){
                    $menudeo = round($price["MENUDEO"],0);
                if($menudeo == $centro){$oferta++;}else{$linea++;};
                }else{
                if($mayoreo == $centro){
                    $menudeo = $caja;
                    $oferta++;
                }elseif(($mayoreo >= 0) && ($mayoreo <= 49)){
                    $menudeo = $mayoreo + 5;
                    $linea++;
                }elseif(($mayoreo >= 50) && ($mayoreo <= 99)){
                    $menudeo = $mayoreo + 10;
                    $linea++;
                }elseif(($mayoreo >= 100) && ($mayoreo <= 499)){
                    $menudeo = $mayoreo + 20;
                    $linea++;
                }elseif(($mayoreo >= 500) && ($mayoreo <= 999)){
                    $menudeo = $mayoreo + 50;
                    $linea++;
                }elseif($mayoreo >= 1000){
                    $menudeo =  $mayoreo + 100; 
                    $linea++;
                }}

                $mayofor = round($mayoreo*$margin,0);
                if(isset($price["MENUDEO"])){
                    $menfor = round($price["MENUDEO"]*$margin,0);
                }else{
                if($mayofor == round($centro*$margin,0)){
                    $menfor = round($caja*$margin,0);
                }elseif(($mayofor >= 0) && ($mayofor <= 49)){
                    $menfor = $mayofor + 5;
                }elseif(($mayofor >= 50) && ($mayofor <= 99)){
                    $menfor = $mayofor + 10;
                }elseif(($mayofor >= 100) && ($mayofor <= 499)){
                    $menfor = $mayofor + 20;
                }elseif(($mayofor >= 500) && ($mayofor <= 999)){
                    $menfor = $mayofor + 50;
                }elseif($mayofor >= 1000){
                    $menfor =  $mayofor + 100; 
                }}
            
                if($costo <= $aaa){
                    if($aaa <= $centro){
                        if($centro <= $especial){
                            if($especial <= $caja){
                                if($caja <= $docena){
                                    if($docena <= $mayoreo){
                                        if($mayoreo <= $menudeo){
                                            $priceslocal [] = [
                                                "MODELO"=>$articulo,
                                                "COSTO"=>$costo,
                                                "AAA"=>$aaa,
                                                "CENTRO"=>$centro,
                                                "ESPECIAL"=>$especial,
                                                "CAJA"=>$caja,
                                                "DOCENA"=>$docena,
                                                "MAYOREO"=>$mayoreo,
                                                "MENUDEO"=>$menudeo
                                            ];
                                            $pricesforeign [] = [
                                                "MODELO"=>$articulo,
                                                "COSTO"=>round($costo*$margin,2),
                                                "CENTRO"=>round($centro*$margin,0),
                                                "ESPECIAL"=>round($especial*$margin,0),
                                                "CAJA"=>round($caja*$margin,0),
                                                "DOCENA"=>round($docena*$margin,0),
                                                "MAYOREO"=>round($mayoreo*$margin,0),
                                                "MENUDEO"=>$menfor
                                            ];
                                        }else{$fail[]="$articulo precio MAYOREO mas alto que MENUDEO ";}
                                    }else{$fail[]="$articulo precio DOCENA mas alto que MAYOREO ";}
                                }else{$fail[]="$articulo precio CAJA mas alto que DOCENA ";}
                            }else{$fail[]="$articulo precio ESPECIAL mas alto que CAJA ";}
                        }else{$fail[]="$articulo precio CENTRO mas alto que ESPECIAL ";}
                    }else{$fail[]="$articulo precio AAA mas alto que CENTRO ";}             
                }else{$fail[]="$articulo precio COSTO mas alto que AAA";}
            }else{$fail[] = "El modelo ".$articulo." no existe. Favor de revisar";}
        }
        if($fail){
            $res = [
                "msg"=>"No se puede serguir con el proceso debido a una falla",
                "fail"=>$fail
            ];
            return response()->json($res);
        }else{
        $pricesloc = $this->localPrices($priceslocal);
        $pricesfor = $this->foreingPrices($pricesforeign);
        $res = [
            "ofertas"=>$oferta,
            "lineas"=>$linea,
            "local"=>$priceslocal,
            "foraneo"=>$pricesforeign,
            "fail"=>$fail,
            "enviolocal"=>$pricesloc,
            "envioforaneo"=>$pricesfor
        ];

        return response()->json($res);
        }
    }
    
    public function localPrices($priceslocal){
        $goals=[
            "factusol"=>[],
            "gvapp"=>[],
        ];
        $fails=[
            "factusol"=>[],
            "gvapp"=>[],
        ];
        $failstores=[];
        $stor=[];
        $date_format = date("d/m/Y");
        $dato = $priceslocal;
        foreach($priceslocal as $price){

            $product = DB::table('products')->where('code',$price['MODELO'])->value('id');

            $aaa = "UPDATE F_LTA SET PRELTA = ". $price['AAA']." WHERE ARTLTA = ? AND TARLTA = 7";
            $exec = $this->conn->prepare($aaa);
            $exec -> execute([$price["MODELO"]]);
            
            $msaaa = DB::table('product_prices')->where('_product',$product)->where('_type',1)->where('_rate',7)->update(['price'=>$price['AAA']]);
            
            $centro = "UPDATE F_LTA SET PRELTA = ". $price['CENTRO']." WHERE ARTLTA = ?  AND TARLTA = 6";
            $exec = $this->conn->prepare($centro);
            $exec -> execute([$price["MODELO"]]);
           
            $mscentro = DB::table('product_prices')->where('_product',$product)->where('_type',1)->where('_rate',6)->update(['price'=>$price['CENTRO']]);
           
            $especial = "UPDATE F_LTA SET PRELTA = ". $price['ESPECIAL']." WHERE ARTLTA = ? AND TARLTA = 5";
            $exec = $this->conn->prepare($especial);
            $exec -> execute([$price["MODELO"]]);
            
            $msespecial = DB::table('product_prices')->where('_product',$product)->where('_type',1)->where('_rate',5)->update(['price'=>$price['ESPECIAL']]);
            
            $caja = "UPDATE F_LTA SET PRELTA = ". $price['CAJA']." WHERE ARTLTA = ? AND TARLTA = 4";
            $exec = $this->conn->prepare($caja);
            $exec -> execute([$price["MODELO"]]);
           
            $mscaja = DB::table('product_prices')->where('_product',$product)->where('_type',1)->where('_rate',4)->update(['price'=>$price['CAJA']]);
            
            $docena = "UPDATE F_LTA SET PRELTA = ". $price['DOCENA']." WHERE ARTLTA = ? AND TARLTA = 3";
            $exec = $this->conn->prepare($docena);
            $exec -> execute([$price["MODELO"]]);
          
            $msdocena = DB::table('product_prices')->where('_product',$product)->where('_type',1)->where('_rate',3)->update(['price'=>$price['DOCENA']]);
    
            $mayoreo = "UPDATE F_LTA SET PRELTA = ". $price['MAYOREO']." WHERE ARTLTA = ?  AND TARLTA = 2";
            $exec = $this->conn->prepare($mayoreo);
            $exec -> execute([$price["MODELO"]]);
          
            $msmayoreo = DB::table('product_prices')->where('_product',$product)->where('_type',1)->where('_rate',2)->update(['price'=>$price['MAYOREO']]);
            
            $menudeo = "UPDATE F_LTA SET PRELTA = ". $price['MENUDEO']." WHERE ARTLTA = ? AND TARLTA = 1";
            $exec = $this->conn->prepare($menudeo);
            $exec -> execute([$price["MODELO"]]);
           
            $msmenudeo = DB::table('product_prices')->where('_product',$product)->where('_type',1)->where('_rate',1)->update(['price'=>$price['MENUDEO']]);
           
            $costo = "UPDATE F_ART SET PCOART = ". $price['COSTO']." , FUMART = ".$date_format." WHERE CODART = ? ";
            $exec = $this->conn->prepare($costo);
            $exec -> execute([$price["MODELO"]]);
            if($exec){$goals['factusol'][]=$price['MODELO']." Precios Modificados factusol";}else{$fails['factusol']= $price['MODELO']." error al actualizar factusol";}
           
            $mscost =DB::table('products')->where('id',$product)->update(['cost'=>$price['COSTO'],'updated_at'=>now()]);
            if($mscost){$goals['gvapp'][]=$price['MODELO']." Precios Modificados mysql";}else{$fails['gvapp']= $price['MODELO']." error al actualizar mysql";}
           

        }

        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 1)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/highPrices";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["prices" => $dato]);//se codifica el arreglo de los proveedores
            //inicio de opciones de curl
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstores[] =$store->alias." Sin conexion";//la sucursal se almacena en sucursales fallidas
            }else{
                // $stor[] = $store->alias." cambios hechos";//de lo contrario se almacenan en sucursales
                $stor[] =["sucursal"=>$store->alias, "mssg"=>$exc];
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
        $res = [
            "goals"=>$goals,
            "fail"=>$fails,
            "sucursales"=>[
                "goals"=>$stor,
                "fail"=>$failstores
            ]
        ];
        return $res;

    }

    public function foreingPrices($pricesforeign){
        $goals=[
            "gvapp"=>[],
        ];
        $fails=[
            "gvapp"=>[],
        ];
        $failstores=[];
        $stor=[];
        $date_format = date("d/m/Y");
        $dato = $pricesforeign;
        foreach($pricesforeign as $price){
            $product = DB::table('products')->where('code',$price['MODELO'])->value('id');
            if($product){
            $mscentro = DB::table('product_prices')->where('_product',$product)->where('_type',2)->where('_rate',6)->update(['price'=>$price['CENTRO']]);
            $msespecial = DB::table('product_prices')->where('_product',$product)->where('_type',2)->where('_rate',5)->update(['price'=>$price['ESPECIAL']]);
            $mscaja = DB::table('product_prices')->where('_product',$product)->where('_type',2)->where('_rate',4)->update(['price'=>$price['CAJA']]);
            $msdocena = DB::table('product_prices')->where('_product',$product)->where('_type',2)->where('_rate',3)->update(['price'=>$price['DOCENA']]);
            $msmayoreo = DB::table('product_prices')->where('_product',$product)->where('_type',2)->where('_rate',2)->update(['price'=>$price['MAYOREO']]);
            $msmenudeo = DB::table('product_prices')->where('_product',$product)->where('_type',2)->where('_rate',1)->update(['price'=>$price['MENUDEO']]);
            $goals['gvapp'][]=$price['MODELO']." Se actualizo correctamente";
            }else{$fails['gvapp'][]= $price['MODELO']." hubo un error al actualizar";}
        }
       
        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 2)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/highPricesForeign";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["prices" => $dato]);//se codifica el arreglo de los proveedores
            //inicio de opciones de curl
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstores[] =$store->alias." Sin conexion";//la sucursal se almacena en sucursales fallidas
            }else{
                // $stor[] = $store->alias." cambios hechos";//de lo contrario se almacenan en sucursales
                $stor[] =["sucursal"=>$store->alias, "mssg"=>$exc];
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
        $res = [
            "goals"=>$goals,
            "fail"=>$fails,
            "sucursales"=>[
                "goals"=>$stor,
                "fail"=>$failstores
            ]
        ];
        return $res;

    }

    public function highPueblaInvoice(Request $request){
        $date = $request->date;
        $failstores=[];
        $stor=[];


        $products = "SELECT F_ART.CODART,F_ART.EANART,F_ART.FAMART,F_ART.DESART,F_ART.DEEART,F_ART.DETART,F_ART.DLAART,F_ART.EQUART,F_ART.CCOART,F_ART.PHAART,F_ART.REFART,F_ART.FTEART,F_ART.PCOART,F_ART.UPPART,F_ART.CANART,F_ART.CAEART,F_ART.UMEART,F_ART.CP1ART,F_ART.CP2ART,F_ART.CP3ART,F_ART.CP4ART,F_ART.CP5ART,F_ART.FALART,F_ART.FUMART,F_ART.MPTART,F_ART.UEQART FROM ((F_ART  INNER JOIN F_LFA ON F_LFA.ARTLFA = F_ART.CODART) INNER JOIN F_FAC ON F_FAC.TIPFAC = F_LFA.TIPLFA AND F_FAC.CODFAC = F_LFA.CODLFA) WHERE F_FAC.CLIFAC = 20  AND  F_FAC.FECFAC >= #".$date."#";
        $exec = $this->conn->prepare($products);
        $exec -> execute();
        $articulos=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($articulos){
        $dat =$this->highPricesPueInvoice($date);
        
        $colsTabProds = array_keys($articulos[0]);
        
        foreach($articulos as $art){
            foreach($colsTabProds as $col){ $art[$col] = utf8_encode($art[$col]); }
            $arti[]=$art;
        }       
                
        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 2)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/insertPub";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["articulos" => $arti]);//se codifica el arreglo de los proveedores
            //inicio de opciones de curl
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstores[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
            }else{
                $stor[] =["sucursal"=>$store->alias, "mssg"=>$exc];
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
        
    
        return response()->json(["articulos"=>[
                                    "goals"=>$stor,
                                    "fail"=>$failstores
                                ],
                                 "precios" => $dat
        ]);
        }
            else{return response()->json("no hay articulos que exportar");}
    }
    
    public function highPricesPueInvoice($date){
        $failstores=[];
        $stor=[];
        // $prices = "SELECT F_LTA.* FROM ((F_LTA  INNER JOIN F_LFA ON F_LFA.ARTLFA = F_LTA.ARTLTA) INNER JOIN F_FAC ON F_FAC.TIPFAC = F_LFA.TIPLFA AND F_FAC.CODFAC = F_LFA.CODLFA) WHERE F_FAC.CLIFAC = 20 AND F_LTA.TARLTA NOT IN (7) AND  F_FAC.FECFAC >= #".$date."#";
       $prices = "SELECT 
       F_LTA.ARTLTA AS CODIGO,
       MAX(iif(F_LTA.TARLTA = 6 , F_LTA.PRELTA ,0 )) AS CENTRO,
       MAX(iif(F_LTA.TARLTA = 5 , F_LTA.PRELTA ,0 )) AS ESPECIAL,
       MAX(iif(F_LTA.TARLTA = 4 , F_LTA.PRELTA ,0 )) AS CAJA,
       MAX(iif(F_LTA.TARLTA = 3 , F_LTA.PRELTA ,0 )) AS DOCENA,
       MAX(iif(F_LTA.TARLTA = 2 , F_LTA.PRELTA ,0 )) AS MAYOREO
       FROM 
       ((F_LTA  
       INNER JOIN F_LFA ON F_LFA.ARTLFA = F_LTA.ARTLTA) 
       INNER JOIN F_FAC ON F_FAC.TIPFAC = F_LFA.TIPLFA AND F_FAC.CODFAC = F_LFA.CODLFA) 
       WHERE F_FAC.CLIFAC = 20 AND F_LTA.TARLTA NOT IN (7) AND  F_FAC.FECFAC >= #".$date."#
       GROUP BY F_LTA.ARTLTA;";
        $exec = $this->conn->prepare($prices);
        $exec -> execute();
        $precios=$exec->fetchall(\PDO::FETCH_ASSOC);
        foreach($precios as $pre){
            $pri[]= $pre;
        }
        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 2)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/insertPricesPub";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["prices" => $pri]);//se codifica el arreglo de los proveedores
            //inicio de opciones de curl
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstores[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
            }else{
                $stor[] =["sucursal"=>$store->alias, "mssg"=>$exc];
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
         $res = [
            "goals"=>$stor,
            "fail"=>$failstores
        ];
        return $res;
    }

    public function highPueblaProducts(Request $request){
        $articulos = $request->all();
        $failstores = [];
        $stor = [];
        $fail = [];
        $codigo = [] ;
        $prices = [] ;
        foreach($articulos as $articulo){

            $products = "SELECT F_ART.CODART,F_ART.EANART,F_ART.FAMART,F_ART.DESART,F_ART.DEEART,F_ART.DETART,F_ART.DLAART,F_ART.EQUART,F_ART.CCOART,F_ART.PHAART,F_ART.REFART,F_ART.FTEART,F_ART.PCOART,F_ART.UPPART,F_ART.CANART,F_ART.CAEART,F_ART.UMEART,F_ART.CP1ART,F_ART.CP2ART,F_ART.CP3ART,F_ART.CP4ART,F_ART.CP5ART,F_ART.FALART,F_ART.FUMART,F_ART.MPTART,F_ART.UEQART FROM F_ART WHERE CODART = ?";
            $exec = $this->conn->prepare($products);
            $exec -> execute([$articulo['codigo']]);
            $producto=$exec->fetch(\PDO::FETCH_ASSOC);
            if($producto){
            $codigo [] = $producto;
            $prices [] = "'".$producto['CODART']."'";
            }else{$fail[]=$articulo['codigo']." no existe el articulo";}
        }
        if($fail){
            return response()->json(["msg"=>"no se puede continuar con el proceso debido a errores","fail"=>$fail]);
        }else{
            $prices = $this->highPueblaProductsPrices($prices);
            $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 2)->get();//se obtienen sucursales de mysql
            foreach($stores as $store){//inicio de foreach de sucursales
                $url = $store->local_domain."/Addicted/public/api/products/insertPubProducts";//se optiene el inicio del dominio de la sucursal
                $ch = curl_init($url);//inicio de curl
                $data = json_encode(["articulos" => $codigo]);//se codifica el arreglo de los proveedores
                //inicio de opciones de curl
                curl_setopt($ch, CURLOPT_POSTFIELDS,$data);//se envia por metodo post
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                //fin de opciones e curl
                $exec = curl_exec($ch);//se executa el curl
                $exc = json_decode($exec);//se decodifican los datos decodificados
                if(is_null($exc)){//si me regresa un null
                    $failstores[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
                }else{
                    $stor[] =["sucursal"=>$store->alias, "mssg"=>$exc];
                }
                curl_close($ch);//cirre de curl
            }//fin de foreach de sucursales
            return response()->json(["articulos"=>[
                "goals"=>$stor,
                "fail"=>$failstores
            ],
             "precios" => $prices
            ]);
        }

    }

    public function highPueblaProductsPrices($codigo){
        $arti = implode(",",$codigo);
        $stor = [];
        $failstores = [];
        $prices = "SELECT 
        F_LTA.ARTLTA AS CODIGO,
        MAX(iif(F_LTA.TARLTA = 6 , F_LTA.PRELTA ,0 )) AS CENTRO,
        MAX(iif(F_LTA.TARLTA = 5 , F_LTA.PRELTA ,0 )) AS ESPECIAL,
        MAX(iif(F_LTA.TARLTA = 4 , F_LTA.PRELTA ,0 )) AS CAJA,
        MAX(iif(F_LTA.TARLTA = 3 , F_LTA.PRELTA ,0 )) AS DOCENA,
        MAX(iif(F_LTA.TARLTA = 2 , F_LTA.PRELTA ,0 )) AS MAYOREO
        FROM F_LTA  
        WHERE F_LTA.TARLTA NOT IN (7) AND F_LTA.ARTLTA IN ($arti)
        GROUP BY F_LTA.ARTLTA;";
        $exec = $this->conn->prepare($prices);
        $exec -> execute();
        $precios=$exec->fetchall(\PDO::FETCH_ASSOC);

        foreach($precios as $pre){
            $pri[]= $pre;
        }
        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 2)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/insertPricesProductPub";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["prices" => $pri]);//se codifica el arreglo de los proveedores
            //inicio de opciones de curl
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstores[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
            }else{
                $stor[] =["sucursal"=>$store->alias, "mssg"=>$exc];
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
         $res = [
            "goals"=>$stor,
            "fail"=>$failstores
        ];
        return $res;
        
    }

    public function replyProducts(Request $request){
        $date = $request->date;
        $stor = [];
        $failstores = [];
        $insertados=[];
        $actualizados=[];
        $fail=[
            "categoria"=>[],
            "codigo_barras"=>[],
            "codigo_corto"=>[], 
        ];
        $mysql=[
            "insertados"=>[],
            "actualizados"=>[],
            "fail"=>[
                "insertados"=>[],
                "actualizados"=>[]
            ]
        ];

        $sql = "SELECT CODART,EANART,FAMART,DESART,DEEART,DETART,DLAART,EQUART,CCOART, PCOART,PHAART,REFART,FUMART,UPPART,UMEART,CP1ART,CP2ART,CP3ART,CP4ART,CP5ART,NPUART,NIAART,DSCART,MPTART,UEQART,CAEART,CANART,FTEART FROM F_ART WHERE FUMART >= #".$date."#";
        $exec = $this->conn->prepare($sql);
        $exec -> execute();
        $articulos=$exec->fetchall(\PDO::FETCH_ASSOC);
        
        if($articulos){
            $colsTab = array_keys($articulos[0]);//llaves de el arreglo 
        foreach($articulos as $articulo){
            foreach($colsTab as $col){ $articulo[$col] = utf8_encode($articulo[$col]); }
            
            $caty = DB::table('product_categories as PC')->join('product_categories as PF', 'PF.id', '=','PC.root')->where('PC.alias', $articulo['CP1ART'])->where('PF.alias', $articulo['FAMART'])->value('PC.id');
            if($caty){
                $arti [] = $articulo;
                $codigos [] = "'".$articulo['CODART']."'";

                    //mysql
                    $provider = DB::table('providers')->where('fs_id',$articulo['PHAART'])->value('id');
                    $assortmen = DB::table('units_measures')->where('name',$articulo['CP3ART'])->value('id');
                    $insms = [
                        "code"=>$articulo['CODART'],
                        "short_code"=>$articulo['CCOART'],
                        "barcode"=>$articulo['EANART'],
                        "description"=>$articulo['DESART'],
                        "label"=>$articulo['DEEART'],
                        "reference"=>$articulo['REFART'],
                        "pieces"=>$articulo['UPPART'],
                        "cost"=>$articulo['PCOART'],
                        "created_at"=>now(),
                        "updated_at"=>now(),
                        "default_amount"=>1,
                        "_kit"=>null,
                        "picture"=>null,
                        "_provider"=>$provider,
                        "_category"=>$caty,
                        "_maker"=>$articulo['FTEART'],
                        "_unit_mesure"=>1,
                        "_state"=>1,
                        "_product_additional"=>null,
                        "_assortment_unit"=>$assortmen
                    ];
                    $pupdms = DB::table('products')->where('code',$articulo['CODART'])->first();
                    if($pupdms){
                        $insms["code"]=$pupdms->code;
                        $updtms = DB::table('products')->where('code',$articulo['CODART'])->update($insms);
                        if($updtms){$mysql['actualizados'][]="Se actualizo el modelo ".$articulo['CODART']." con exito";}else{$mysql['fail']['actualizados'][]="hubo problemas al actualizar el modelo ".$articulo['CODART'];}
                    }else{
                        $shor = DB::table('products')->where('short_code',$articulo['CCOART'])->first();
                        if($shor){$mysql['fail']['insertados']="El codigo corto ".$art["CODIGO CORTO"]." a sido otorgado a el codigo ".$shor->code."no se puede duplicar";}else{
                        $insmysql = DB::table('products')->insert($insms);
                        if($insmysql){$mysql['insertados'][]="Se inserto correctamente el modelo ".$articulo['CODART'];}else{$mysql['fail']['insertados'][]="el modelo ".$articulo['CODART']." tuvo un error al insertar";}
                        }
                    }
            }else{$fail['categoria'][]="no existe la categoria ".$articulo['CP1ART']." de la familia ".$articulo['FAMART']." de el producto ".$articulo['CODART'];} 
        }
        
        $precios = $this->replyProductsPrices($codigos);
        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 1)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/replyProducts";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["articulos" => $arti]);//se codifica el arreglo de los proveedores
            //inicio de opciones de curl
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstores[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
                // $failstores[] =["sucursal"=>$store->alias, "mssg"=>$exec];//la sucursal se almacena en sucursales fallidas

            }else{
                $stor[] =["sucursal"=>$store->alias, "mssg"=>$exc];
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
         $res = [
            "articulos"=>[
            "goals"=>$stor,
            "fail"=>$failstores,
            "mysql"=>$mysql,
            "msfails"=>$fail
            
            ],
            "precios"=>$precios
        ];
        return $res;
        }else{return response()->json("No se encontro ningun articulos insertado o modificado en la fecha ".$date);}

    }

    public function replyProductsPrices($codigos){
        $arti = implode(",",$codigos);
        $stor = [];
        $failstores = [];
        $prices = "SELECT 
        F_LTA.ARTLTA AS CODIGO,
        MAX(iif(F_LTA.TARLTA = 6 , F_LTA.PRELTA ,0 )) AS CENTRO,
        MAX(iif(F_LTA.TARLTA = 5 , F_LTA.PRELTA ,0 )) AS ESPECIAL,
        MAX(iif(F_LTA.TARLTA = 4 , F_LTA.PRELTA ,0 )) AS CAJA,
        MAX(iif(F_LTA.TARLTA = 3 , F_LTA.PRELTA ,0 )) AS DOCENA,
        MAX(iif(F_LTA.TARLTA = 2 , F_LTA.PRELTA ,0 )) AS MAYOREO,
        MAX(iif(F_LTA.TARLTA = 2 , F_LTA.PRELTA ,0 )) AS MENUDEO
        FROM F_LTA  
        WHERE F_LTA.TARLTA NOT IN (7) AND F_LTA.ARTLTA IN ($arti)
        GROUP BY F_LTA.ARTLTA";
        $exec = $this->conn->prepare($prices);
        $exec -> execute();
        $precios=$exec->fetchall(\PDO::FETCH_ASSOC);

        foreach($precios as $pre){
            $pri[]= $pre;
        }
        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 1)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/replyProductsPrices";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["prices" => $pri]);//se codifica el arreglo de los proveedores
            //inicio de opciones de curl
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstores[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
            }else{
                $stor[] =["sucursal"=>$store->alias, "mssg"=>$exc];
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
        $pricesms="SELECT * FROM F_LTA WHERE ARTLTA IN ($arti)";
        $exec = $this->conn->prepare($pricesms);
        $exec -> execute();
        $pric=$exec->fetchall(\PDO::FETCH_ASSOC);
        foreach($pric as $propri){
            $idms = DB::table('products')->where('code',$propri['ARTLTA'])->value('id');
            $updatems = DB::table('product_prices')->where('_rate',$propri['TARLTA'])->where('_product',$idms)->where('_type',1)->update(['price'=>$propri['PRELTA']]);
        }




         $res = [
            "goals"=>$stor,
            "fail"=>$failstores
        ];
        return $res;
    }

    public function additionalsBarcode(Request $request){
        $stor = [];
        $failstores = [];
        $mysql = [];
        $failmysql = [];
        $date = $request->date;
        $addbar = "SELECT F_EAN.* FROM F_EAN INNER JOIN F_ART ON F_ART.CODART = F_EAN.ARTEAN WHERE FUMART = #".$date."#";
        $exec = $this->conn->prepare($addbar);
        $exec -> execute();
        $barcodes=$exec->fetchall(\PDO::FETCH_ASSOC);
        foreach($barcodes as $barcode){
            $add [] = $barcode;
            $idms [] = $barcode['ARTEAN']; 
            $idfs [] = "'".$barcode['ARTEAN']."'";
        }

        DB::statement("SET SQL_SAFE_UPDATES = 0;");//se desactiva safe update
        DB::statement("SET FOREIGN_KEY_CHECKS = 0;");//se desactivan las llaves foraneas
        $delete = DB::table('product_additionals_barcodes AS PAB')->join('products AS P','P.id','PAB._product')->whereIN('P.code',$idms)->delete();//se vacia la tabla de clientes
        DB::statement("SET SQL_SAFE_UPDATES = 1;");//se activan las llaves foraneas
        DB::statement("SET FOREIGN_KEY_CHECKS = 1;");//se activa safe update
        foreach($add as $isadd){
            $idpro = DB::table('products')->where('code',$isadd['ARTEAN'])->value('id');
            if($idpro){
            $ins = [
                "_product"=>$idpro,
                "additional_barcode"=>$isadd['EANEAN']
            ];
            $mysql = "insertados correctamente";
            $dbs = DB::table('product_additionals_barcodes')->insert($ins);
            }else{$failmysql[]="El producto ".$isadd['ARTEAN']." no existe";}
        }
        
        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/additionalsBarcode";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["addbarcodes" => $add,"ids"=>$idfs]);//se codifica el arreglo de los proveedores
            //inicio de opciones de curl
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data);//se envia por metodo post
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos decodificados
            if(is_null($exc)){//si me regresa un null
                $failstores[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
            }else{
                $stor[] =["sucursal"=>$store->alias, "mssg"=>$exc];
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
        $res = [
            "sucursal"=>[
                "fail"=>$failstores,
                "goal"=>$stor
            ],
            "mysql"=>[
                "fail"=>$failmysql,
                "goal"=>$mysql
            ]
            ];
        return response()->json($res);
    }

}
