<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public function __construct(){
        $access = env("GENERAL_FILE");//conexion a access de sucursal
        if(file_exists($access)){
        try{  $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }

        $access = env("ACCESS_FILE");//conexion a access de sucursal
        if(file_exists($access)){
        try{  $this->con  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }
    }

    public function createuser(Request $request){
        $idnew = [
            "usuario"=>[],
            "dependiente"=>[],
            "agente"=>[]
        ];
        $replyage = null;
        $replyusu = null;
        $tpvper = [];
        $permisos = [];
        $program = [];
        $iduser = $request->id;
        $workpoint = env('WORKPOINT');

        $user = DB::table('users AS U')->join('user_roles AS UR','UR.id','U._rol')->where('U.id',$iduser)->select('U.*','UR.type_rol')->first();

        if($user){
            $nombre = implode(explode(" ",$user->name));
            $apellido = implode(explode(" ",$user->surnames));
            if($user->type_rol == 1 || $user->_rol == 9 || $user->_rol == 10){
                if($user->_rol == 9 || $user->_rol == 10){
                    $tpvper = 3;
                    $uss = "SELECT MAX(CODDEP) as CODIGO FROM T_DEP";
                    $exec = $this->con->prepare($uss);
                    $exec -> execute();
                    $usc=$exec->fetch(\PDO::FETCH_ASSOC);

                    $age = "SELECT MAX(CODAGE) as CODIGO FROM F_AGE";
                    $exec = $this->con->prepare($age);
                    $exec -> execute();
                    $uscag=$exec->fetch(\PDO::FETCH_ASSOC);

                    $nombre = $user->name." ".$user->surnames;

                    $agente = [
                        $uscag['CODIGO']+1,//codage
                        now()->format('Y-m-d'),//falage
                        $nombre,//nomage,
                    ];

                    $depe = [
                        $usc['CODIGO']+1,//coddep
                        $nombre,//nomdep
                        $tpvper,//perdep
                        '357N73N77G53Q75O114K92J52C58D105G',//cladep,
                        $uscag['CODIGO']+1//AGEDEP
                    ];
                    try{
                        $insertage = "INSERT INTO F_AGE (CODAGE,FALAGE,NOMAGE) VALUES (?,?,?)";
                        $exec = $this->con->prepare($insertage);
                        $exec -> execute($agente);

                        $insertdep = "INSERT INTO T_DEP (CODDEP,NOMDEP,PERDEP,CLADEP,AGEDEP) VALUES (?,?,?,?,?)";
                        $exec = $this->con->prepare($insertdep);
                        $exec -> execute($depe);
                    }catch (\PDOException $e){ die($e->getMessage());}
                    $insertms = DB::table('users')->where('id',$iduser)->update(['TPV_id'=>$usc['CODIGO']+1]);

                    $replyage = $this->Replyagents($uscag['CODIGO']+1,$usc['CODIGO']+1,$iduser);

                    $idnew['dependiente']=$usc['CODIGO']+1;
                    $idnew['agente']=$uscag['CODIGO']+1;
                }

                try{
                    $usfs = "SELECT MAX(CODUSU) as CODIGO FROM F_USU";
                    $exec = $this->conn->prepare($usfs);
                    $exec -> execute();
                    $usefac=$exec->fetch(\PDO::FETCH_ASSOC);
                }catch (\PDOException $e){ die($e->getMessage());}
                $accessfile = DB::table('stores')->where('id',$workpoint)->value('access_file');

                $usuario = [
                    $usefac['CODIGO']+1,
                    $nombre." ".$apellido,
                    '55T65H75P85U95G363E68I94C82D87U87A103T11',//12345
                    'FS'.$accessfile,
                    1,
                    1,
                    1,
                    'GEN',
                    1,
                    10001,
                    10001,
                    10001,
                    10001,
                    10001,
                    10001,
                    10001,
                    10001,
                    10001,
                    10001,
                    1,
                    1
                ];

                $insertusu = "INSERT INTO F_USU (CODUSU,NOMUSU,CLAUSU,EMPUSU,GESUSU,CONUSU,LABUSU,ALMARTUSU,APPUSU,ALBUSU,FACUSU,PREUSU,PPRUSU,FREUSU,PCLUSU,RECUSU,ENTUSU,FABUSU,FRDUSU,IDIUSU,ELIUSU) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $exec = $this->conn->prepare($insertusu);
                $exec -> execute($usuario);

                if($user->_rol == 1 || $user->_rol == 2){
                    $permisos = [
                        "PermisosTipoFactuSOL_".$usefac['CODIGO']+1,
                        3
                    ];
                    $program = [
                        "PermisosFactuSOL_".$usefac['CODIGO']+1,
                        '',
                        1
                    ];
                }else {
                    $permisos = [
                        "PermisosTipoFactuSOL_".$usefac['CODIGO']+1,
                        1
                    ];
                    $program = [
                        "PermisosFactuSOL_".$usefac['CODIGO']+1,
                        // '0-N|5-N|10-N|15-N|20-N|25-N|30-N|35-N|40-N|45-N|50-N|55-N|60-N|65-N|70-N|75-N|80-N|85-N|90-N|95-N|110-N|115-N|145-N|150-N|155-N|160-N|165-N|170-N|175-N|180-N|185-N|190-N|195-N|2-N|10000-N|10005-N|10006-N|10510-N|10511-N|10515-N|10520-N|10525-N|10530-N|10535-N|10540-N|10545-N|10010-N|10011-N|10015-N|10020-N|10025-N|1010015-N|10030-N|1010030-N|10035-N|1010035-N|10040-N|10045-N|10050-N|10170-N|10180-N|10185-N|10190-N|10193-N|10195-N|10200-N|10205-N|10210-N|10215-N|10220-N|10225-N|10227-N|10230-N|10235-N|10240-N|10090-N|10102-N|10105-N|10110-N|10115-N|10130-N|10135-N|10140-N|10145-N|10150-N|10155-N|10160-N|10165-N|10320-N|10325-N|1010325-N|10330-N|10335-N|10340-N|10345-N|11000-N|11005-N|11006-N|11075-N|11080-N|11085-N|11090-N|11095-N|11105-N|11110-N|11112-N|11115-N|11120-N|11125-N|11130-N|11135-N|11140-N|11145-N|11500-N|11505-N|11506-N|11507-N|11730-N|12220-N|12225-N|11735-N|11740-N|11510-N|11515-N|11520-N|11525-N|11530-N|11535-N|11540-N|11545-N|11550-N|11555-N|11560-N|11565-N|11570-N|11575-N|11580-N|11590-N|11591-N|11595-N|11600-N|11605-N|11610-N|11615-N|11620-N|11625-N|11627-N|11630-N|11635-N|11640-N|11645-N|11650-N|11655-N|11660-N|11665-N|11670-N|11675-N|11680-N|11685-N|11690-N|11695-N|11700-N|11705-N|11710-N|11715-N|11720-N|11725-N|11750-N|11755-N|11760-N|11765-N|11770-N|11775-N|11780-N|1011780-N|11785-N|11790-N|11795-N|11800-N|10805-N|10505-N|10506-N|10060-N|10065-N|10070-N|10075-N|10080-N|11672-N|10555-N|10565-N|10570-N|10095-N|10100-N|11017-N|11015-N|11056-N|11025-N|11030-N|11035-N|11060-N|11065-N|11040-N|11045-N|11050-N|11055-N|11057-N|12200-N|12205-N|12215-N|12210-N|14075-N|14080-N|14085-N|14090-N|14095-N|14097-N|10083-N|11070-N|11072-N|10120-N|11677-N|14067-N|14068-N|14069-N|11681-N|11682-N|14070-N|14150-N|14155-N|14160-N|14165-N|14170-N|14175-N|14177-N|14178-N|14190-N|14195-N|14180-N|14185-N|14186-N|14187-N|14188-N|14189-N|14193-N|14191-N|10250-N|10255-N|10260-N|10265-N|10270-N|10272-N|10275-N|10280-N|10285-N|10290-N|13000-N|13005-N|13006-N|13010-N|13015-N|13025-N|13030-N|13035-N|13040-N|13050-N|13062-N|13055-N|13060-N|13065-N|13070-N|13080-N|13085-N|13095-N|13100-N|13102-N|13110-N|13115-N|13120-N|13125-N|13130-N|13132-N|13135-N|13140-N|13145-N|13150-N|13155-N|13160-N|13165-N|13170-N|13175-N|13180-N|13185-N|13190-N|13195-N|13200-N|13205-N|13207-N|13210-N|13215-N|13220-N|13225-N|13230-N|13235-N|13240-N|13245-N|13250-N|13255-N|13260-N|13265-N|13270-N|13275-N|13280-N|13285-N|13290-N|13295-N|13300-N|13303-N|13305-N|13310-N|13315-N|13320-N|13325-N|13330-N|13335-N|13340-N|13345-N|13350-N|13355-N|13360-N|13365-N|13370-N|13367-N|13375-N|13380-N|13385-N|13390-N|13395-N|13400-N|13405-N|13410-N|13415-N|13420-N|13435-N|13440-N|13445-N|13450-N|13455-N|13460-N|13465-N|13470-N|13475-N|13480-N|13485-N|13490-N|13495-N|13500-N|13505-N|13520-N|13525-N|13530-N|13535-N|13537-N|13540-N|13545-N|13550-N|13555-N|13560-N|13565-N|13570-N|13575-N|13580-N|13585-N|13590-N|13595-N|13600-N|13605-N|13610-N|13615-N|13620-N|13625-N|13630-N|13635-N|13640-N|13645-N|13650-N|13655-N|13660-N|13665-N|13670-N|13675-N|13680-N|13685-N|13690-N|13695-N|13700-N|13705-N|13710-N|13715-N|14000-N|14005-N|14006-N|14010-N|14015-N|14020-N|14025-N|14030-N|14035-N|14040-N|14043-N|14045-N|14050-N|14055-N|14060-N|14065-N|12500-N|12525-N|12530-N|12535-N|12540-N|12545-N|12547-N|12550-N|12555-N|12560-N|12565-N|12570-N|12575-N|12580-N|12585-N|12590-N|12600-N|12605-N|12610-N|12620-N|11805-N|11885-N|11886-N|11940-N|11945-N|11950-N|11955-N|11960-N|11965-N|11970-N|11975-N|11980-N|11985-N|11990-N|12005-N|12010-N|12015-N|12025-N|12030-N|12040-N|12045-N|12050-N|12055-N|12100-N|14100-N|14105-N|14110-N|14130-N|14120-N|14125-N|14127-N|14140-N|14145-N|14500-N|14590-N|14592-N|14591-N|14565-N|14566-N|14585-N|14586-N|14587-N|14588-N|14589-N|14530-N|14570-N|14580-N|14510-N|14515-N|14520-N|14525-N|14540-N|14545-N|14550-N|14555-N|14560-N|5-N|50000-N|50005-N|50006-N|50050-N|50052-N|50055-N|50057-N|50060-N|50335-N|50010-N|50012-N|50015-N|50020-N|50025-N|50030-N|50032-N|50035-N|50045-N|50046-N|51550-N|51560-N|51600-N|51610-N|51620-N|50197-N|50255-N|50260-N|50915-N|50920-N|50925-N|50930-N|50935-N|50945-N|50064-N|50065-N|50066-N|50176-N|50315-N|50395-N|50397-N|50190-N|50195-N|50039-N|50040-N|55600-N|55610-N|55620-N|50067-N|50070-N|50080-N|50085-N|50095-N|50075-N|50090-N|50105-N|50110-N|50115-N|50120-N|50125-N|50130-N|50135-N|50140-N|50100-N|50149-N|50150-N|50155-N|50160-N|50170-N|50175-N|50165-N|50179-N|50180-N|50185-N|50200-N|50205-N|50210-N|50215-N|50220-N|50225-N|50230-N|50235-N|50240-N|50241-N|50242-N|50245-N|50250-N|50320-N|50325-N|50145-N|50265-N|50305-N|50270-N|50275-N|50280-N|50285-N|50290-N|50295-N|50300-N|50303-N|50340-N|50345-N|50390-N|50350-N|50385-N|50360-N|50365-N|50375-N|50380-N|50262-N|50330-N|50061-N|50062-N|50063-N|50069-N|50681-N|50682-N|50400-N|50401-N|50402-N|50440-N|50445-N|50455-N|50460-N|50465-N|50405-N|50410-N|50415-N|50420-N|50425-N|50430-N|50432-N|50435-N|50675-N|50680-N|50685-N|50690-N|50695-N|50700-N|50705-N|50710-N|50715-N|50720-N|50725-N|50734-N|50735-N|50730-N|50740-N|50745-N|50750-N|50755-N|50760-N|50470-N|50475-N|50480-N|50485-N|50490-N|50495-N|50500-N|50505-N|50510-N|50515-N|50520-N|50525-N|50530-N|50535-N|50540-N|50545-N|50550-N|50555-N|50560-N|50565-N|50570-N|50575-N|50580-N|50585-N|50590-N|50595-N|50597-N|50600-N|50605-N|50610-N|50615-N|50620-N|50625-N|50630-N|50635-N|50640-N|50645-N|50650-N|50655-N|50660-N|50665-N|50765-N|50767-N|50770-N|50775-N|50780-N|50785-N|50790-N|50795-N|50800-N|50805-N|50810-N|50815-N|50820-N|50825-N|50830-N|50835-N|50840-N|50845-N|50850-N|50851-N|50852-N|50855-N|50860-N|50865-N|50870-N|50875-N|50880-N|50885-N|50887-N|50890-N|50893-N|50896-N|50900-N|50905-N|50910-N|50911-N|50912-N|50913-N|50914-N|50950-N|50954-N|50955-N|51030-N|51075-N|51080-N|51085-N|51090-N|51095-N|51100-N|51105-N|51110-N|51115-N|51120-N|51125-N|51135-N|51140-N|51150-N|51155-N|51157-N|51168-N|51170-N|51200-N|51201-N|51202-N|51203-N|51230-N|51231-N|51235-N|51285-N|51286-N|51287-N|51288-N|51289-N|51265-N|51270-N|51280-N|51210-N|51215-N|51220-N|51225-N|51240-N|51245-N|51250-N|51255-N|51260-N',
                        '0-N|5-N|15-N|20-N|25-N|30-N|35-N|40-N|45-N|50-N|55-N|60-N|65-N|70-N|75-N|80-N|85-N|90-N|95-N|110-N|115-N|145-N|150-N|155-N|160-N|165-N|170-N|175-N|180-N|185-N|190-N|195-N|2-N|10000-N|10005-N|10006-N|10510-N|10511-N|10515-N|10520-N|10525-N|10530-C|10535-C|10540-C|10010-N|10011-N|10015-N|10020-N|10025-N|1010015-N|10030-N|1010030-N|10035-N|1010035-N|10040-N|10045-N|10050-N|10170-N|10180-N|10185-N|10190-N|10193-N|10195-N|10200-N|10205-N|10210-N|10215-N|10220-N|10225-N|10227-N|10230-N|10235-N|10240-N|10090-N|10102-N|10105-N|10110-N|10115-N|10130-N|10135-N|10140-N|10145-N|10150-N|10155-N|10160-N|10165-N|10320-N|10325-N|1010325-N|10330-N|10335-N|10340-N|10345-N|11000-N|11005-N|11006-N|11075-N|11080-N|11090-N|11095-C|11105-N|11115-N|11120-N|11125-N|11130-N|11140-N|11145-N|11500-N|11505-N|11506-N|11507-N|11730-N|12220-N|12225-N|11735-N|11740-N|11510-N|11515-N|11520-N|11525-N|11530-N|11535-N|11540-N|11545-N|11550-N|11555-N|11560-N|11565-N|11570-N|11575-N|11580-N|11590-N|11591-N|11595-N|11600-N|11605-N|11610-N|11615-N|11620-N|11625-N|11627-N|11630-N|11635-N|11640-N|11645-N|11650-N|11655-N|11660-N|11665-N|11670-N|11675-N|11680-N|11685-N|11690-N|11695-N|11697-N|11700-N|11705-N|11710-N|11715-N|11720-N|11725-N|11750-N|11755-N|11760-N|11765-N|11770-N|11775-N|11780-N|1011780-N|11785-N|11790-N|11795-N|11800-N|10805-N|10505-N|10506-N|10060-N|10065-N|10070-N|10075-N|10080-N|11672-N|10555-N|10565-N|10570-N|10095-N|10100-N|11015-C|11025-C|11030-C|11035-C|11060-C|11065-N|11040-N|11045-N|11050-N|11055-N|11057-N|12200-N|12205-N|12215-N|12210-N|14075-N|14080-N|14085-N|14090-N|14095-N|14097-N|10083-N|11070-N|11072-N|10120-N|11678-N|14071-N|11677-N|14067-N|14068-N|14069-N|11681-N|11682-N|14601-N|14605-N|14610-N|14615-N|14620-N|14070-N|14150-N|14155-N|14160-N|14165-N|14170-N|14175-N|14177-N|14178-N|14190-N|14195-N|14180-N|14185-N|14186-N|14187-N|14188-N|14189-N|14193-N|14191-N|10250-N|10255-N|10260-N|10265-N|10270-N|10272-N|10275-N|10280-N|10285-N|10290-N|13000-C|13005-C|13006-C|13010-C|13015-C|13025-C|13030-C|13035-C|13040-C|13050-C|13062-C|13055-C|13060-C|13065-C|13070-C|13080-C|13085-C|13095-C|13100-C|13102-C|13110-C|13115-C|13120-C|13125-C|13130-C|13132-C|13135-C|13140-C|13145-C|13150-C|13155-C|13160-C|13165-C|13170-C|13175-C|13180-C|13185-C|13190-C|13195-C|13200-C|13205-C|13207-C|13210-C|13215-C|13220-C|13225-C|13230-C|13235-C|13240-C|13245-C|13250-C|13255-C|13260-C|13265-C|13270-C|13275-C|13280-C|13285-C|13290-C|13295-C|13300-C|13303-C|13305-C|13310-C|13315-C|13320-C|13325-C|13330-C|13335-C|13340-C|13345-C|13350-C|13355-C|13360-C|13365-C|13370-C|13367-C|13375-C|13380-C|13385-C|13390-C|13395-C|13400-C|13405-C|13410-C|13415-C|13420-C|13435-C|13440-C|13445-C|13450-C|13455-C|13460-C|13465-C|13470-C|13475-C|13480-C|13485-C|13490-C|13495-C|13500-C|13505-C|13520-C|13525-C|13530-C|13535-C|13537-C|13540-C|13545-C|13550-C|13555-C|13560-C|13565-C|13570-C|13575-C|13580-C|13585-C|13590-C|13595-C|13600-C|13605-C|13610-C|13615-C|13620-C|13625-C|13630-C|13635-C|13640-C|13645-C|13650-C|13655-C|13660-C|13665-C|13670-C|13675-C|13680-C|13685-C|13690-C|13695-C|13700-C|13705-C|13710-C|13715-C|14000-N|14005-N|14006-N|14010-N|14015-N|14020-N|14025-N|14030-N|14035-N|14040-N|14043-N|14045-N|14050-N|14055-N|14060-N|14065-N|12500-N|12525-N|12530-N|12535-N|12540-N|12545-N|12547-N|12550-N|12555-N|12560-N|12565-N|12570-N|12575-N|12580-N|12585-N|12590-N|12600-N|12605-N|12610-N|12620-N|11805-N|11885-N|11886-N|11940-N|11945-N|11950-N|11955-N|11960-N|11965-N|11970-N|11975-N|11980-N|11985-N|11990-N|12005-N|12010-N|12015-N|12025-N|12030-N|12040-N|12045-N|12050-N|12055-N|12100-N|14100-N|14105-N|14110-N|14130-N|14120-N|14125-N|14127-N|14140-N|14145-N|14500-N|14590-N|14592-N|14591-N|14565-N|14566-N|14585-N|14586-N|14587-N|14588-N|14589-N|14530-N|14570-N|14580-N|14510-N|14515-N|14520-N|14525-N|14540-N|14545-N|14550-N|14555-N|14560-N|5-C|50000-C|50005-C|50006-C|50050-C|50052-C|50055-C|50057-C|50060-C|50335-C|50010-C|50012-C|50015-C|50020-C|50025-C|50030-C|50032-C|50035-C|50045-C|50046-C|51550-C|51560-C|51600-C|51610-C|51620-C|50197-C|50255-C|50260-C|50915-C|50920-C|50925-C|50930-C|50935-C|50945-C|50064-C|50065-C|50066-C|50176-C|50315-C|50395-C|50397-C|50190-C|50195-C|50039-C|50040-C|55600-C|55610-C|55620-C|50067-C|50070-C|50080-C|50085-C|50095-C|50075-C|50090-C|50105-C|50110-C|50115-C|50120-C|50125-C|50130-C|50135-C|50140-C|50100-C|50149-C|50150-C|50155-C|50160-C|50170-C|50175-C|50165-C|50179-C|50180-C|50185-C|50200-C|50205-C|50210-C|50215-C|50220-C|50225-C|50230-C|50235-C|50240-C|50241-C|50242-C|50245-C|50250-C|50320-C|50325-C|50145-C|50265-C|50305-C|50270-C|50275-C|50280-C|50285-C|50290-C|50295-C|50300-C|50303-C|50340-C|50345-C|50390-C|50350-C|50385-C|50360-C|50365-C|50375-C|50380-C|50262-C|50330-C|50061-C|50062-C|50063-C|50069-C|50681-C|50682-C|50400-C|50401-C|50402-C|50440-C|50445-C|50455-C|50460-C|50465-C|50405-C|50410-C|50415-C|50420-C|50425-C|50430-C|50432-C|50435-C|50675-C|50680-C|50685-C|50690-C|50695-C|50700-C|50705-C|50710-C|50715-C|50720-C|50725-C|50734-C|50735-C|50730-C|50740-C|50745-C|50750-C|50755-C|50760-C|50470-C|50475-C|50480-C|50485-C|50490-C|50495-C|50500-C|50505-C|50510-C|50515-C|50520-C|50525-C|50530-C|50535-C|50540-C|50545-C|50550-C|50555-C|50560-C|50565-C|50570-C|50575-C|50580-C|50585-C|50590-C|50595-C|50597-C|50600-C|50605-C|50610-C|50615-C|50620-C|50625-C|50630-C|50635-C|50640-C|50645-C|50650-C|50655-C|50660-C|50665-C|50765-C|50767-C|50770-C|50775-C|50780-C|50785-C|50790-C|50795-C|50800-C|50805-C|50810-C|50815-C|50820-C|50825-C|50830-C|50835-C|50840-C|50845-C|50850-C|50851-C|50852-C|50855-C|50860-C|50865-C|50870-C|50875-C|50880-C|50885-C|50887-C|50890-C|50893-C|50896-C|50900-C|50905-C|50910-C|50911-C|50912-C|50913-C|50914-C|50950-C|50954-C|50955-C|51030-C|51075-C|51080-C|51085-C|51090-C|51095-C|51100-C|51105-C|51110-C|51115-C|51120-C|51125-C|51135-C|51140-C|51150-C|51155-C|51157-C|51168-C|51170-C|51200-C|51201-C|51202-C|51203-C|51230-C|51231-C|51235-C|51285-C|51286-C|51287-C|51288-C|51289-C|51265-C|51270-C|51280-C|51210-C|51215-C|51220-C|51225-C|51240-C|51245-C|51250-C|51255-C|51260-C',
                        1
                    ];
                }
                try{
                    $insertper = "INSERT INTO F_CFG (CODCFG,NUMCFG) VALUES (?,?)";
                    $exec = $this->conn->prepare($insertper);
                    $exec -> execute($permisos);

                    $insertpro = "INSERT INTO F_CFG (CODCFG,TEXCFG,TIPCFG) VALUES (?,?,?)";
                    $exec = $this->conn->prepare($insertpro);
                    $exec -> execute($program);
                }catch (\PDOException $e){ die($e->getMessage());}

                $insertms = DB::table('users')->where('id',$iduser)->update(['FS_id'=>$usefac['CODIGO']+1]);

                $idnew['usuario']=$usefac['CODIGO']+1;

                // $replyusu = $this->Replyuser($usefac['CODIGO']+1,$iduser);

            } else if($user->type_rol == 2){

                $uss = "SELECT MAX(CODDEP) as CODIGO FROM T_DEP";
                $exec = $this->con->prepare($uss);
                $exec -> execute();
                $usc=$exec->fetch(\PDO::FETCH_ASSOC);

                $age = "SELECT MAX(CODAGE) as CODIGO FROM F_AGE";
                $exec = $this->con->prepare($age);
                $exec -> execute();
                $uscag=$exec->fetch(\PDO::FETCH_ASSOC);

                $nombre = $user->name." ".$user->surnames;

                $agente = [
                    $uscag['CODIGO']+1,//codage
                    now()->format('Y-m-d'),//falage
                    $nombre,//nomage,
                ];

                $tpvper = 2;

                $depe = [
                    $usc['CODIGO']+1,//coddep
                    $nombre,//nomdep
                    $tpvper,//perdep
                    '357N73N77G53Q75O114K92J52C58D105G',//cladep,
                    $uscag['CODIGO']+1//AGEDEP
                ];
                try{
                    $insertage = "INSERT INTO F_AGE (CODAGE,FALAGE,NOMAGE) VALUES (?,?,?)";
                    $exec = $this->con->prepare($insertage);
                    $exec -> execute($agente);

                    $insertdep = "INSERT INTO T_DEP (CODDEP,NOMDEP,PERDEP,CLADEP,AGEDEP) VALUES (?,?,?,?,?)";
                    $exec = $this->con->prepare($insertdep);
                    $exec -> execute($depe);
                }catch (\PDOException $e){ die($e->getMessage());}

            $insertms = DB::table('users')->where('id',$iduser)->update(['TPV_id'=>$usc['CODIGO']+1]);
            $replyage = $this->Replyagents($uscag['CODIGO']+1,$usc['CODIGO']+1);

            $idnew['dependiente']=$usc['CODIGO']+1;
            $idnew['agente']=$uscag['CODIGO']+1;
            }

            $res = [
                "usuario"=>$replyusu,
                "agentes"=>$replyage,
                "nuevosid"=>$idnew
            ];


            return response()->json($res,200);



        }else{return response()->json("No se encuentra el usuario",404);}
    }

    public function Replyuser($iduser,$idms){
        $failstor = [];
        $stor = [];
    try{
        $usfs = "SELECT * FROM F_USU WHERE CODUSU = $iduser";
        $exec = $this->conn->prepare($usfs);
        $exec -> execute();
        $usefac=$exec->fetch(\PDO::FETCH_ASSOC);

        $permi = "SELECT * FROM F_CFG WHERE CODCFG LIKE '%".$iduser."'";
        $exec = $this->conn->prepare($permi);
        $exec -> execute();
        $usef=$exec->fetchall(\PDO::FETCH_ASSOC);
        $colsTab = array_keys($usef[0]);
    }catch (\PDOException $e){ die($e->getMessage());}
        foreach($usef as $permi){foreach($colsTab as $col){ $permi[$col] = utf8_encode($permi[$col]); }
            $permisos[]=$permi;
        }



        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 1)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/agents/replyuser";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["usuario" => $usefac,"permisos"=>$permisos]);//se codifica el arreglo de los proveedores
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
                $failstor[] =$store->alias." Sin conexion";//la sucursal se almacena en sucursales fallidas
                // $failstor[] =["sucursal"=>$store->alias, "mssg"=>$exec];//la sucursal se almacena en sucursales fallidas

            }else{
                $stor [] = ["sucursal"=>$store->alias, "mssg"=>$exc];//de lo contrario se almacenan en sucursales
                $upduser = DB::table('user_id_externals')->UpdateOrInsert(['_store'=>$store->id,'_user'=>$idms],['factusol'=>$iduser]);
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
        $res = [
            "fail"=>$failstor,
            "goals"=>$stor
        ];
        return $res;


    }

    public function Replyagents($idagen,$iddep,$idms){
        $failstor = [];
        $stor = [];
    try{
        $uss = "SELECT * FROM T_DEP WHERE CODDEP = $iddep";
        $exec = $this->con->prepare($uss);
        $exec -> execute();
        $usc=$exec->fetch(\PDO::FETCH_ASSOC);

        $age = "SELECT * FROM F_AGE WHERE CODAGE = $idagen";
        $exec = $this->con->prepare($age);
        $exec -> execute();
        $uscag=$exec->fetch(\PDO::FETCH_ASSOC);
    }catch (\PDOException $e){ die($e->getMessage());}

        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 1)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/agents/replyagents";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["agente" => $uscag,"dependiente" => $usc]);//se codifica el arreglo de los proveedores
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
                $failstor[] =$store->alias." Sin conexion";//la sucursal se almacena en sucursales fallidas
                // $failstor[] =["sucursal"=>$store->alias, "mssg"=>$exec];
            }else{
                $stor [] = ["sucursal"=>$store->alias, "mssg"=>$exc];//de lo contrario se almacenan en sucursales
                $upduser = DB::table('user_id_externals')->UpdateOrInsert(['_store'=>$store->id,'_user'=>$idms],['tpvsol'=>$iddep]);
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales

        $res = [
            "fail"=>$failstor,
            "goals"=>$stor
        ];

        return $res;
    }
}
