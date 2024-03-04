<?php
// print_r($_SERVER);
/*
If you put the whole webauthn directory in the www document root and put an index.php in there
which just includes this file, it should then work. Alternatively set it as a link to this file.
*/

require_once(__DIR__.'/vendor/autoload.php');

/* In this example, the user database is simply a directory of json files
  named by their username (urlencoded so there are no weird characters
  in the file names). For simplicity, it's in the HTML tree so someone
  could look at it - you really, really don't want to do this for a
  live system */
define('USER_DATABASE', __DIR__.'/users/.users');
if (!file_exists(USER_DATABASE)) {
    if (!@mkdir(USER_DATABASE)) {
        error_log(sprintf('Cannot create user database directory - is the html directory writable by the web server? If not: "mkdir %s; chmod 777 %s"', USER_DATABASE, USER_DATABASE));
        die(sprintf("cannot create %s - see error log", USER_DATABASE));
    }
}
session_start();

function oops($s)
{
    http_response_code(400);
    echo "{$s}\n";
    exit;
}

function userpath($username)
{
    $username = str_replace('.', '%2E', $username);
    return sprintf('%s/%s.json', USER_DATABASE, urlencode($username));
}

function getuser($username)
{
    $user = @file_get_contents(userpath($username));
    if (empty($user)) {
        oops('user not found');
    }
    $user = json_decode($user);
    if (empty($user)) {
        oops('user not json decoded');
    }
    return $user;
}

function saveuser($user)
{
    file_put_contents(userpath($user->name), json_encode($user));
}

/* A post is an ajax request, otherwise display the page */
if (!empty($_POST)) {

    try {

        $webauthn = new \Davidearl\WebAuthn\WebAuthn($_SERVER['HTTP_HOST']);

        switch (TRUE) {

            case isset($_POST['registerusername']):
                /* initiate the registration */
                $username = $_POST['registerusername'];
                $crossplatform = !empty($_POST['crossplatform']) && $_POST['crossplatform'] == 'Yes';
                $userid = md5(time() . '-' . rand(1, 1000000000));

                if (file_exists(userpath($username))) {
                    oops("user '{$username}' already exists");
                }

                /* Create a new user in the database. In principle, you can store more
         than one key in the user's webauthnkeys,
         but you'd probably do that from a user profile page rather than initial
         registration. The procedure is the same, just don't cancel existing
         keys like this.*/
                $user = (object)[
                    'name' => $username,
                    'id' => $userid,
                    'webauthnkeys' => $webauthn->cancel()
                ];
                saveuser($user);
                $_SESSION['username'] = $username;
                $j = ['challenge' => $webauthn->prepareChallengeForRegistration($username, $userid, $crossplatform)];
                break;

            case isset($_POST['register']):
                /* complete the registration */
                if (empty($_SESSION['username'])) {
                    oops('username not set');
                }
                $user = getuser($_SESSION['username']);

                /* The heart of the matter */
                $user->webauthnkeys = $webauthn->register($_POST['register'], $user->webauthnkeys);

                /* Save the result to enable a challenge to be raised agains this
         newly created key in order to log in */
                saveuser($user);
                $j = 'ok';

                break;

            case isset($_POST['loginusername']):
                /* initiate the login */
                $username = $_POST['loginusername'];
                $user = getuser($username);
                $_SESSION['loginname'] = $user->name;

                /* note: that will emit an error if username does not exist. That's not
         good practice for a live system, as you don't want to have a way for
         people to interrogate your user database for existence */

                $j['challenge'] = $webauthn->prepareForLogin($user->webauthnkeys);

                /* Save user again, which sets server state to include the challenge expected */
                saveuser($user);
                break;

            case isset($_POST['login']):
                /* authenticate the login */
                if (empty($_SESSION['loginname'])) {
                    oops('username not set');
                }
                $user = getuser($_SESSION['loginname']);

                if (!$webauthn->authenticate($_POST['login'], $user->webauthnkeys)) {
                    http_response_code(401);
                    echo 'failed to authenticate with that key';
                    exit;
                }
                /* Save user again, which sets server state to include the challenge expected */
                saveuser($user);
                $j = 'ok';

                break;

            default:
                http_response_code(400);
                echo "unrecognized POST\n";
                break;
        }
    } catch (Exception $ex) {
        oops($ex->getMessage());
    }

    header('Content-type: application/json');
    echo json_encode($j);
    exit;
}

?>
<!doctype html>
<html lang="en">

<head>
    <title>Visyfy QR Scanner, Bio Metric Auth</title>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS v5.2.1 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />

    <link rel="stylesheet" href="css/styles.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

</head>

<body>

    <div class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container">
            <div class="d-flex flex-row align-items-center">
                <div class="p-2">
                    <img src="images/logo_nav.png" class="img-fluid rounded-start" alt="Visyfy network" style="max-width: 60px;">
                </div>
                <div class="p-2">
                    <h1>Visyfy Network</h1>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row mt-5">
            <div class="col">
                <h1>QR Scanner Demo1(ScanApp)</h1>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <div id="v-qr-scanner">
                            <div class="section1">
                                <div id="qr-reader"></div>
                                <div id="qr-reader-results"></div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col">
                                <a id="v-qr-scanner-btn" class="btn btn-primary">QR Scanner</a>
                                <a id="v-qr-cancel" class="btn btn-primary">Close QRScanner</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col">
                <h1>WebAuthn</h1>

                <div class='cerror'></div>
                <div class='cdone'></div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <h3>User Registration</h3>

                                <form id='iregisterform' name='iregisterform' action='/' method='POST'>
                                    <div class="mb-3">
                                        <label for="" class="form-label">Enter a new username (eg email address): </label>
                                        <input type="text" class="form-control" name="registerusername" id="" aria-describedby="helpId" placeholder="" />
                                    </div>
                                    <div class="mb-3">
                                        <label for="" class="form-label">Cross-platform?<sup>*</sup></label>
                                        <select class="form-select form-select-lg" name="cp" id="">
                                            <option value=''>(choose one)</option>
                                            <option>No</option>
                                            <option>Yes</option>
                                        </select>
                                    </div>
                                    <p class="form-text text-muted">* Use cross-platform 'Yes' when you have a removable device, like
                                        a Yubico key, which you would want to use to login on different
                                        computers; say 'No' when your device is attached to the computer (in
                                        that case in Windows 10 1903 release, your login
                                        is linked to Windows Hello and you can use any device it supports
                                        whether registered with that device or not, but only on that
                                        computer). The choice affects which device(s) are offered by the
                                        browser and/or computer security system.</p>


                                    <button type="submit" class="btn btn-primary">
                                        Submit
                                    </button>

                                </form>
                                <div class='cdokey' id='iregisterdokey'>
                                    Do your thing: press button on key, swipe fingerprint or whatever
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <h2>User Login</h2>
                                <form id='iloginform' name='iloginform' action='/' method='POST'>
                                    <div class="mb-3">
                                        <label for="" class="form-label">Enter existing username:</label>
                                        <input type="text" class="form-control" name="loginusername" id="" aria-describedby="helpId" placeholder="" />

                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        Submit
                                    </button>


                                </form>
                                <div class='cdokey' id='ilogindokey'>
                                    Do your thing: press button on key, swipe fingerprint or whatever
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <script type="application/javascript">
        <?php
        echo file_get_contents(__DIR__.'/js/webauthnregister.js');
        echo file_get_contents(__DIR__.'/js/webauthnauthenticate.js');
        echo "var AJAX_URL='".$_SERVER['SCRIPT_NAME']."';";
        ?>
    </script>
    <!-- Bootstrap JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>

    <script src="js/scripts.js" type="text/javascript"></script>
</body>

</html>