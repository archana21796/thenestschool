<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once "vendor/autoload.php";

$mail = new PHPMailer;

function respond($status, $message){
     header('Content-Type: application/json');
    echo json_encode(["status" => $status, "message" => $message]);
    exit;
}

function clean($val){
    return htmlspecialchars(trim($val), ENT_QUOTES);
}

// Google reCAPTCHA validation
$recaptcha_secret = '6LdQWEosAAAAAIN0kcfk4tSoXGjjPorsd-sOskSj';
$recaptcha_response = $_POST['g-recaptcha-response'];
$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';

$recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
$recaptcha_result = json_decode($recaptcha);

if (!$recaptcha_result->success) {
    respond("error", "Invalid reCAPTCHA.");
}

$sender_name = isset($_POST['name']) ? clean($_POST['name']) : "" ;

$sender_mobile = isset($_POST['mobile']) ? clean($_POST['mobile']) : "" ;

$email = isset($_POST['email']) ? $_POST['email'] : "" ;

$resource = isset($_POST['resource']) ? clean($_POST['resource']) : "" ;

$experience = isset($_POST['experience']) ? clean($_POST['experience']) : "" ;

$formname = isset($_POST['formname']) ? clean($_POST['formname']) : "" ;
if ($formname == ""){
    respond("error", "Form is Invalid");
}

$btncta = isset($_POST['btncta']) ? clean($_POST['btncta']) : "" ;

$ip = isset($_POST['ip']) ? clean($_POST['ip']) : "" ;

$source = isset($_POST['source']) ? clean($_POST['source']) : "-" ;

$page_url = isset($_POST['page_url']) ? clean($_POST['page_url']) : "-" ;

$date = isset($_POST['posted_date']) ? clean($_POST['posted_date']) : "" ;

$formatted_date = (new DateTime($date))->format('d-m-Y h:i A');

$required_fields = [
    'Name' => $sender_name,
    'Mobile Number' => $sender_mobile,
    'Email' => $email,
    'Work Experience' => $experience
];

foreach ($required_fields as $field_name => $value) {
    if (trim($value) === '') {
        respond("error", "$field_name is required");
    }
}


$allowedEmails = [
    'rogith@onemg.co',
    'test@onemg.co',
    'admin@onemg.co'
];

if (in_array(strtolower($email), array_map('strtolower', $allowedEmails))) {
    respond("error", "All OK");
}

$db = mysqli_connect("162.241.85.104","onemg3fl_nest_campaign","X1UNVsNmahjo","onemg3fl_nest_campaign");

if (!$db) {
    respond("error", "Connection failed");
}

$stmt = $db->prepare(
    "INSERT INTO ns_download_brochure (name, mobile, email, resource, experience, btncta, ip, source, page_url, posted_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    respond("error", $db->error);
}

$stmt->bind_param(
    "ssssssssss",
    $sender_name, $sender_mobile, $email, $resource, $experience, $btncta, $ip, $source, $page_url, $date
);

if (!$stmt->execute()) {
    respond("error", $stmt->error);
}

$stmt->close();

/* Whatsapp bot */

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://webhooksapp.vleafy.com/bot/inbound/69a83dd602e28c7ee4e1dd5a',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
    "name": "'.$sender_name.'",
    "number": "91'.$sender_mobile.'"
}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json'
  ),
));

$response = curl_exec($curl);

curl_close($curl);

/* Mail Setup */

$mail->isSMTP();            
//Set SMTP host name                          
$mail->Host = "mail.onemg.com";
//Set this to true if SMTP host requires authentication to send email
$mail->SMTPAuth = true;                          
//Provide username and password     
$mail->Username = "test-mail@onemg.com";                 
$mail->Password = "ms@ojZc-4]jH";                           
//If SMTP requires TLS encryption then set it
$mail->SMTPSecure = "tls";                           
//Set TCP port to connect to 
$mail->Port = 587;                                   

$mail->From = "test-mail@onemg.com";
$mail->FromName = "The Nest School - 2026 Enquire";

$mail->addAddress("dm@onemg.co", "Recepient Name");

//$mail->addCC('gowsalya@onemg.co');
//$mail->addBCC('rogith@onemg.co');

$mail->isHTML(true);

$mail->Subject = "$sender_name";
$mail->Body = "<html>
<head>
<title>The Nest School Enqurie - 2026</title>
<meta charset='UTF-8'>
<style>
p {
	font-family: 'Google Sans',Roboto,RobotoDraft,Helvetica,Arial,sans-serif;
	color: #333333;
}


</style>
</head>
<body style='background: #f7f7f7; padding: 20px;'>
    <table border='0' cellpadding='0' cellspacing='0' width='600' align='center' style='background:#ffffff;font-family:'Open Sans','Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.7em'>
        <tbody>
            <tr>
                <td style='padding:0'>
                    <table border='0' cellpadding='0' cellspacing='0' width='600' style='min-width:600px;border:none;border-left:1px solid #dedede;background: #ffffff;'>
                        <tbody>
                            <tr>
                                 <td align='center' style='padding: 10px;'>
									<img src='https://onemgcloud.com/nest-campaign/assets/images/nest-logo.png' alt='The Nest School' style='max-height:100px;'>
                                 </td>
                            </tr>
                        </tbody>
                    </table>
                    <table border='0' cellpadding='0' cellspacing='0' width='600' style='min-width:600px;border:none;border-left:1px solid #dedede; background: #ffffff;'>
                        <tbody>
                            <tr>
                                <td style='padding:0'>
                                    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-bottom: 1px solid #c3c3c3;'>
                                        <tbody>
                                            <tr>
												<table cellpadding='10' cellspacing='0' width='100%' border='0' align='center' style='color:#000; padding:20px;'>
													
														<tr style='width:100%;'>
															<td style='width:30%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>From</td>	
															<td style='width:1%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>:</td>
															<td style='width:69%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>The Nest School Landing Page - 2026</td>	
														</tr>
														<tr style='width:100%;'>
															<td style='width:30%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>Name</td>	
															<td style='width:1%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>:</td>
															<td style='width:69%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>$sender_name</td>	
														</tr>
														<tr style='width:100%;'>
															<td style='width:30%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>Mobile Number</td>	
															<td style='width:1%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>:</td>
															<td style='width:69%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>$sender_mobile</td>	
														</tr>
														
														<tr style='width:100%;'>
															<td style='width:30%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>Email</td>	
															<td style='width:1%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>:</td>
															<td style='width:69%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>$email</td>	
														</tr>
														
														<tr style='width:100%;'>
															<td style='width:30%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>Grade</td>	
															<td style='width:1%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>:</td>
															<td style='width:69%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>$experience</td>	
														</tr>

														<tr style='width:100%;'>
															<td style='width:30%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>Form</td>	
															<td style='width:1%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>:</td>
															<td style='width:69%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>$formname</td>	
														</tr>
														<tr>
															<td style='width:30%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>Post Ip</td>	
															<td style='width:1%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>:</td>
															<td style='width:69%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>$ip</td>	
														</tr>
													
														<tr>
															<td style='width:30%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>Page</td>	
															<td style='width:1%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>:</td>
															<td style='width:69%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>$page_url</td>	
														</tr>

														<tr>
															<td style='width:30%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>Posted Date</td>	
															<td style='width:1%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>:</td>
															<td style='width:69%; border-bottom: 1px dashed #1111114f;font-size: 14px;'>$formatted_date</td>	
														</tr>

												</table>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>

            </tr>
        </tbody>
    </table>
    <body>
</html>";
$mail->AltBody = "Alternative Message For The Nest School.";

if(!$mail->send()) 
{
    respond("error", "Mail sending failed");
} 
else 
{
     respond("success", "Message has been sent successfully!");
}    

respond("success", "Form submitted successfully!");

    
exit();

?>