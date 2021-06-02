<?php
session_start();
// date_default_timezone_set('Pacific/Honolulu');
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

$mail->Username = "wilberparedesg@gmail.com";
$mail->Password = "1461217Arca!";
$mail->SetFrom('wilberparedesg@gmail.com', utf8_decode('Barranquilla verde'));

$mail->Subject = utf8_decode("Nuevo reporte | Barranquilla verde");
$mail->AltBody = "";



if(isset($_GET['case'])){
    $case=$_GET['case'];
}

// Variables de get


if(isset($_POST['nameuser'])){
  $nameuser = strtolower($_POST['nameuser']);
}
if(isset($_POST['email'])){
  $email = strtolower($_POST['email']);
}
if(isset($_POST['pass'])){
  $pass = $_POST['pass'];
}
if(isset($_POST['cellphone'])){
  $cellphone = $_POST['cellphone'];
}
if(isset($_POST['name_complete'])){
  $name_complete = ucwords(strtolower($_POST['name_complete']));
}
if(isset($_POST['id_device'])){
  $id_device = $_POST['id_device'];
}

$createtable = array(
  'data' => array()
);

function base64url_encode($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

switch ($case) {

    case 'viewNoti':
      $viewNoti = "UPDATE notificaciones SET view = 0 WHERE codusuario_fk = :codigo";
      $paramsViewNoti = array(':codigo' => $_SESSION["CA_codigo_usuCA"]);

      if(query($viewNoti, $paramsViewNoti)){
        $json =  json_encode(array('success' => true));
      }else{
        $json =  json_encode(array('success' => false));
      }
    break;

    case 'loadPerfiles2':
      $sql = "SELECT codperfil as cod, nombre_perfil as nombre, estado_perfil FROM perfil2 WHERE estado_perfil = 'on'";
      $table = table($sql);
      $json = json_encode($table);
    break;

    case 'prueba':
      $json = json_encode(array('success' => true ));
    break;


    /************************ procesos para miperfil.php **************************/

    case 'createAccount':

      $insert = "INSERT INTO usuarios (nameuser, email, pass, cellphone, name_complete, id_device) VALUES (:nameuser, :email, :pass, :cellphone, :name_complete, :id_device) RETURNING id_us";
      $paramsInsert = array(
                            ':nameuser' => $nameuser,
                            ':email' => $email, 
                            ':pass' => $pass,
                            ':cellphone' => $cellphone,
                            ':name_complete' => $name_complete,
                            ':id_device' => $id_device,
                          );
      $datarow = DataRow($insert,$paramsInsert);

      if($datarow != -1){
        $json = json_encode(array("success" => true, "id_usu" => $datarow["id_us"]));
      }else{
        $json = json_encode(array("success" => false,"message" => "Error al crear usuario"));
      }
    break;


    case 'uploadFotoPerfil':
      if($_FILES['img-perfil']['tmp_name']!=""){
        $file=$_FILES["img-perfil"]['name'];
        $extension= explode(".",$file) ;
        $url="../assets/".$_GET['nombphoto'].".".$extension[1];                       
        $urlFoto='assets/'.$_GET['nombphoto'].".".$extension[1];

        if (move_uploaded_file($_FILES['img-perfil']['tmp_name'],$url)) {
          // $sql="UPDATE usuarios SET foto='". $urlFoto."' WHERE codigo_usu='".$_SESSION['CA_codigo_usuCA']."'"; 
          // query($sql);
          // $_SESSION['CA_foto']=$urlFoto;
          $json = json_encode(array("success" => true, "mensaje"=>"Foto subida exitosamente. ".$urlFoto)); 
        }

      }else{
        // print_r()
      $json = json_encode(array("success" =>false,"message"=>"Campo vacio.", "img" => $_FILES['img-perfil'] ));
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

    
    
  /************************  FIN procesos para miperfil.php ****************************/

}
echo $json;

?>

