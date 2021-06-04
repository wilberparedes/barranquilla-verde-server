<?php
session_start();
// date_default_timezone_set('America/Bogota');
// date_default_timezone_set('Europe/Amsterdam');
header('content-type: application/json; charset=utf-8');

require_once 'PDOConn.php';


require("phpmailer/class.phpmailer.php");
require("phpmailer/class.smtp.php");

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->SMTPAuth = true;
$mail->SMTPKeepAlive = true; 
$mail->SMTPSecure = "tls";
$mail->SMTPDebug  = 0;
$mail->Host = "smtp.gmail.com";
$mail->Port = 587;
// $mail->Username = "wilberparedes@gmail.com";
// $mail->Password = "1461217arca#";
// $mail->SetFrom('wilberparedes@gmail.com', utf8_decode('Corporación Universitaria Americana'));
$tz_object = new DateTimeZone('America/Bogota');
$objDateTime = new DateTime('NOW');
$objDateTime->setTimezone($tz_object);

$mail->Username = "wilberparedesg@gmail.com";
$mail->Password = "1461217Arca!";
$mail->SetFrom('wilberparedesg@gmail.com', utf8_decode('Barranquilla verde'));

$mail->Subject = utf8_decode("Nuevo reporte | Barranquilla verde | ".$objDateTime->format('d/m/Y h:i a'));
$mail->AltBody = "";


if(isset($_GET['case'])){
  $case = $_GET['case'];
}

if(isset($_POST['iduser'])){
  $iduser = $_POST['iduser'];
}
if(isset($_POST['idparque'])){
  $idparque = $_POST['idparque'];
}
if(isset($_POST['zonanovedad'])){
  $zonanovedad = $_POST['zonanovedad'];
}
if(isset($_POST['comentario'])){
  $comentario = trim($_POST['comentario']);
}
if(isset($_POST['nameuser'])){
  $nameuser = strtolower(trim($_POST['nameuser']));
}
if(isset($_POST['email'])){
  $email = strtolower(trim($_POST['email']));
}
if(isset($_POST['pass'])){
  $pass = trim($_POST['pass']);
}
if(isset($_POST['cellphone'])){
  $cellphone = trim($_POST['cellphone']);
}
if(isset($_POST['name_complete'])){
  $name_complete = ucwords(strtolower(trim($_POST['name_complete'])));
}
if(isset($_POST['id_device'])){
  $id_device = $_POST['id_device'];
}

switch ($case) {

    case 'createAccount':

      $sqlEmail = "SELECT email FROM usuarios WHERE email = :email";
      $rowEmail = row($sqlEmail, array(':email' => $email));

      $sqlCellphone = "SELECT cellphone FROM usuarios WHERE cellphone = :cellphone";
      $rowCellphone = row($sqlCellphone, array(':cellphone' => $cellphone));

      if($rowEmail != ""){
        $json = json_encode(array("success" => false, "message" => "Ya existe un usuario registrado con el Correo electrónico ingresado."));
      }else if( $rowCellphone != ""){
        $json = json_encode(array("success" => false, "message" => "Ya existe un usuario registrado con el Número de teléfono ingresado."));
      }else{
        $insert = "INSERT INTO usuarios (nameuser, email, pass, cellphone, name_complete, id_device) VALUES (:nameuser, :email, :pass, :cellphone, :name_complete, :id_device) RETURNING id_us";
        $paramsInsert = array(
                              ':nameuser' => $nameuser,
                              ':email' => $email, 
                              ':pass' => sha1($pass),
                              ':cellphone' => $cellphone,
                              ':name_complete' => $name_complete,
                              ':id_device' => $id_device,
                            );
        $datarow = DataRow($insert,$paramsInsert);
        if($datarow != -1){
          $objDateTime = new DateTime('NOW');
          $json = json_encode(array("success" => true, "id" => $datarow["id_us"], "accessToken" => sha1($rowEmail["id_us"]."".$objDateTime->format('c')) ));
        }else{
          $json = json_encode(array("success" => false,"message" => "Error al crear usuario"));
        }
      }
    break;

    case 'Login':

      $sqlEmail = "SELECT id_us, nameuser, email, pass, cellphone, name_complete, id_device FROM usuarios WHERE email = :email";
      $rowEmail = row($sqlEmail, array(':email' => $email));

      if($rowEmail != ""){
        
        if($rowEmail["pass"] == sha1($pass)){

          $sql="UPDATE usuarios SET id_device=:id_device WHERE id_us= :idus"; 
          query($sql, array(':id_device' => $id_device, ':idus' => $rowEmail["id_us"]));

          $objDateTime = new DateTime('NOW');
          $json = json_encode(array(
            "success" => true, 
            "accessToken" => sha1($rowEmail["id_us"]."".$objDateTime->format('c')),
            "id" => $rowEmail["id_us"], 
            "nameuser" => $rowEmail["nameuser"], 
            "email" => $rowEmail["email"], 
            "cellphone" => $rowEmail["cellphone"], 
            "name_complete" => $rowEmail["name_complete"], 
            "id_device" => $id_device, 
          ));
        }else{
          $json = json_encode(array("success" => false,"message" => "Contraseña incorrecta."));
        }

      }else{
        $json = json_encode(array("success" => false,"message" => "El Correo electrónico ingresado no existe."));
      }
      
    break;


    case 'uploadFotoPerfil':
      if($_FILES['img-perfil']['tmp_name']!=""){
        $file=$_FILES["img-perfil"]['name'];
        $extension= explode(".",$file) ;
        $url="../assets/".$_GET['nombphoto'].".".$extension[1];                       
        $urlFoto='assets/'.$_GET['nombphoto'].".".$extension[1];

        if (move_uploaded_file($_FILES['img-perfil']['tmp_name'],$url)) {
          $json = json_encode(array("success" => true, "mensaje"=>"Foto subida exitosamente. ".$urlFoto)); 

          
        }

      }else{
        // print_r()
      $json = json_encode(array("success" =>false,"message"=>"Campo vacio.", "img" => $_FILES['img-perfil'] ));
      }
    break;


    case 'saveReport':
      if($_FILES['img-perfil']['tmp_name']!=""){

        $sqlParque = "SELECT * FROM parques WHERE id_prq = :idparque";
        $rowParque = row($sqlParque, array(':idparque' => $idparque));

        $file=$_FILES["img-perfil"]['name'];
        $extension= explode(".",$file) ;
        $newnamefile = $_GET['nombphoto'].".".$extension[1];
        $url="../assets/".$newnamefile;                       
        $urlFoto='assets/'.$newnamefile;

        if (move_uploaded_file($_FILES['img-perfil']['tmp_name'],$url)) {
          
          $insert = "INSERT INTO reportes (id_usuario_fk, id_parque_fk, tipo, comentario, imagen, fecha) VALUES (:iduser, :idparque, :zonanovedad, :comentario, :imagen, :fecha) RETURNING id_rp";
          $paramsInsert = array(
                                ':iduser' => $iduser,
                                ':idparque' => $idparque, 
                                ':zonanovedad' => $zonanovedad,
                                ':comentario' => $comentario,
                                ':imagen' => $newnamefile,
                                ':fecha' => $objDateTime->format('Y-m-d H:i:s'),
                              );
          $datarow = DataRow($insert,$paramsInsert);
          if($datarow != -1){
            

            $html = "<!DOCTYPE html>";
            $html .= "<html>";
            $html .= "<head>";
            $html .= "<title>REPORTE DE NOVEDAD | BARRANQUILLA VERDE</title>";
            $html .= "<meta charset='UTF-8'>";
            $html .= "<style> 
                body{
                    text-align:center;
                }
              /*button*/
                .button{
                  display: inline-block;
                  padding:0 10px;
                  color: #4c4c4c;
                  height: 40px;
                  text-align: center;
                  line-height: 35px;
                  white-space: nowrap;
                    cursor: pointer;
                  box-sizing: border-box; 
                  border: 1px solid #CCCCCC;
                  margin-bottom:10px;
                  border-radius:3px;
                  font-weight: bold;
                  outline: 0;
                  text-decoration: none;
                }
                .primary-button{
                  color: #fff !important;
                  background: #004e93!important;
                  border: 1px solid #004e93;
                }
            </style>";
            $html .="</head>";
            $html .="<body>";
            $html .= '<table style="width:100%;max-width:600px" width="100%" cellspacing="0" cellpadding="0" border="0" align="center">';
            $html .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td role="modules-container" style="padding:0px 0px 0px 0px;color:#000000;text-align:left" width="100%" bgcolor="#ffffff" align="left">';
            $html .= '<table style="table-layout:fixed" width="100%" cellspacing="0" cellpadding="0" border="0">';
            $html .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td style="font-size:6px;line-height:10px;padding:0px 0px 0px 0px" valign="top" align="center">';
            $html .= '<img style="display:block;max-width:100%!important;width:100%;height:auto!important" src="../assets/header-email.png" width="600" border="0">';
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';
      
            $html .= '<table style="table-layout:fixed" width="100%" cellspacing="0" cellpadding="0" border="0">';
            $html .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td style="padding:0px 0px 20px 0px">';
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';

            $html .= '<table style="table-layout:fixed" width="100%" cellspacing="0" cellpadding="0" border="0">';
            $html  .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td style="padding:5px 0px 0px 0px;line-height:22px;text-align:inherit" valign="top" height="100%">';
            $html .= '<div>';
            $html .= '<span style="font-size:18px";font-family:arial,helvetica,sans-serif">';
            $html .= '<strong>'.$rowParque["name_prq"].'</strong>';
            $html .='</span></div>';
            $html  .='</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td style="padding:5px 0px 5px 0px;line-height:22px;text-align:inherit" valign="top" height="100%">';
            $html .= '<div>';
            $html .= '<span style="font-size:14px";font-family:arial,helvetica,sans-serif">';
            $html .= '<strong>Dirección: </strong> '.$rowParque["address"];
            $html .='</span></div>';
            $html  .='</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td style="padding:0px 0px 5px 0px;line-height:22px;text-align:inherit" valign="top" height="100%">';
            $html .= '<div>';
            $html .= '<span style="font-size:14px";font-family:arial,helvetica,sans-serif">';
            $html .= '<strong>Barrio: </strong> '.$rowParque["neighborhood"];
            $html .='</span></div>';
            $html  .='</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td style="padding:10px 0px 5px 0px;line-height:22px;text-align:inherit" valign="top" height="100%">';
            $html .= '<div>';
            $html .= '<span style="font-size:16px";font-family:arial,helvetica,sans-serif">';
            $html .= '<strong>Detalle de reporte: </strong>';
            $html .='</span></div>';
            $html  .='</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td style="padding:0px 0px 5px 0px;line-height:22px;text-align:inherit" valign="top" height="100%">';
            $html .= '<div>';
            $html .= '<span style="font-size:14px";font-family:arial,helvetica,sans-serif">';
            $html .= '<strong>Tipo: </strong>';
            if($zonanovedad == 'zonasverdes'){
              $html .= 'Novedad Zonas verdes';
            }
            else if($zonanovedad == 'gimnasio'){
              $html .= 'Novedad Gimnasio biosaludable';
            }
            else{
              $html .= 'Novedad Parques';
            }

            $html .='</span></div>';
            $html  .='</td>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td style="padding:0px 0px 5px 0px;line-height:22px;text-align:inherit" valign="top" height="100%">';
            $html .= '<div>';
            $html .= '<span style="font-size:14px";font-family:arial,helvetica,sans-serif">';
            $html .= '<strong>Comentarios: </strong> <br />';
            $html .= $comentario;
            $html .='</span></div>';
            $html  .='</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td style="padding:0px 0px 5px 0px;line-height:22px;text-align:inherit" valign="top" height="100%">';
            $html .= '<div>';
            $html .= '<span style="font-size:14px";font-family:arial,helvetica,sans-serif">';
            $html .= '<strong>Evidencia: </strong>';
            $html .='</span></div>';
            $html  .='</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td style="padding:0px 0px 0px 0px;line-height:22px;text-align:inherit" valign="top" height="100%">';
            $html .= '<img style="display:block;max-width:70%!important;width:100%;height:auto!important" src="'.$url.'">';
            $html  .='</td>';
            $html .= '</tr>';

            $html .= '</tbody>';
            $html .= '</table>';
      
            $html .="</body>";
            $html .="</html>";
            $mail->MsgHTML($html);
            $mail->AddAddress($email,"");
            $mail->AddBCC("wilberparedes@gmail.com","");

            $mail->IsHTML(true);
            $mail->smtpConnect(
            array(
              "ssl" => array(
                  "verify_peer" => false,
                  "verify_peer_name" => false,
                  "allow_self_signed" => true
              )
            )
            );

            if ($mail->Send()) {
              $json = json_encode(array("success" => true, "idrp" => $datarow["id_rp"]));
            }else{
              $json=json_encode(array("success"=>true,  "idrp" => $datarow["id_rp"],  "message"=>$mail->ErrorInfo));
            }

          }else{
            $json = json_encode(array("success" => false,"message" => "Error registrar novedad, por favor, intente de nuevo más tarde"));
          }

        }

      }else{
        $json = json_encode(array("success" =>false,"message"=> "Ocurrió un error al subir imagen, por favor, intente de nuevo", "img" => $_FILES['img-perfil'] ));
      }
    break;


    case 'editUsuario':
      $update = "UPDATE usuarios SET nombre = :nombre WHERE codigo_usu = :codusu";
      $params = array(':nombre' => $nombre, ':codusu'=>$_SESSION['CA_codigo_usuCA']);

      if(query($update, $params)){

        $_SESSION['CA_nombre']=$nombre;
        $json = json_encode(array("success"=>true));
      }else{
        $json = json_encode(array("success"=>false,"mensaje" => "No se Actualizó la información. Por favor, intentelo de nuevo"));
      }
    break;

    case 'editContrasena':
      $select = "SELECT * FROM usuarios WHERE codigo_usu = :codusuario";
      $paramsselect = array(':codusuario' => $_SESSION['CA_codigo_usuCA']);
      $row = row($select,$paramsselect);

      if($row != ''){
        if($row['password']==sha1($contraActual)){
          $update = "UPDATE usuarios SET password = :password WHERE codigo_usu = :codusuario";
          $params = array(':password'=> sha1($nuevaContra), ':codusuario'=>$_SESSION['CA_codigo_usuCA']);

          if(query($update, $params)){
            $json = json_encode(array("success"=>true));
          }else{
            $json = json_encode(array("success"=>false,"mensaje" => "No se Actualizó la información. Por favor, intentelo de nuevo"));
          }
        }else{
          $json = json_encode(array("success"=>false,"mensaje" => "La contraseña actual ingresada no es correcta"));
        }
      }
    break;


    case 'Prueba':
      $json = json_encode(array("success"=>true, "datetime" => $objDateTime->format('Y-m-d H:i:s') ));
    break;

    case 'LoadParques':
      $sql = "SELECT id_prq as id, name_prq AS name, latitude, longitude, address, neighborhood, gym, soccer_field FROM parques AS prq
      WHERE prq.state_prq = 1";
      $table = table($sql);
      $parquesall = array();
      foreach ($table as $datarow => $data) {

        $sql1 = "SELECT url FROM imagenes_parques
        WHERE state_ip = 1 AND id_parque_fk = :idparque";
        $table1 = table($sql1, array(':idparque' => $data["id"]));

        array_push($parquesall, array("id" => $data["id"], "name" => $data["name"], "latitude" => $data["latitude"], "longitude" => $data["longitude"], "address" => $data["address"], "neighborhood" => $data["neighborhood"], "gym" => $data["gym"], "soccer_field" => $data["soccer_field"], "images" => $table1));

      }

      $json = json_encode($parquesall);
    break;

}
echo $json;

?>

