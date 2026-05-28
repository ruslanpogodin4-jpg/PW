<?php
session_start();
define('dir', dirname(__FILE__));
header("Content-Type: text/html; charset=utf-8");
$url = explode("/install.php", strtolower($_SERVER['PHP_SELF']));
$url = reset($url);
if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) $protocol = "https://"; else $protocol = "http://";
$url = $protocol . $_SERVER['HTTP_HOST'] . $url;
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Установка IWEB</title>
    <link rel='shortcut icon' type='image/x-icon' href='<?php echo $url; ?>/system/template/images/favicon.ico'/>
    <link rel="stylesheet" href="<?php echo $url; ?>/system/template/css/bootstrap.min.css">
    <style>
        body {
            background: #1c2326;
            /*background: url("padded.png");*/
            /*background-size: cover;*/
        }

        .content {
            background: #272f32;
            width: 600px;
            margin: 0 auto;
            margin-top: 30px;
            color: #c2c2c2;
            font-size: 12px;
            padding: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        .next {
            position: relative;
            margin-bottom: 10px;
        }

        .container-fluid input[type=text] {
            padding-bottom: 5px;
            background: #1f2426;
            border: 1px solid #161a1c;
            color: white;
        }

        .container-fluid input[type=text]:focus {
            background: #192023;
            border: 1px solid #161a1c;
            color: white;
        }

        .container-fluid input[type=password] {
            padding-bottom: 5px;
            background: #1f2426;
            border: 1px solid #161a1c;
            color: white;
        }

        .container-fluid input[type=password]:focus {
            background: #192023;
            border: 1px solid #161a1c;
            color: white;
        }

        .container-fluid select {
            padding-bottom: 5px;
            background: #1f2426;
            border: 1px solid #161a1c;
            color: white;
        }

        .container-fluid select:focus {
            background: #192023;
            border: 1px solid #161a1c;
            color: white;
        }
    </style>
</head>
<body>
<div class="container-fluid">

    <div class="row">


        <?php
        $get = (isset($_GET['step'])) ? $_GET['step'] : "";
        switch ($get) {
            case 1:
                $sd = (is_writable(dir . "/system/data")) ? "<span style='color: green'>Разрешено</span>" : "<span style='color: red'>Запрещено</span>";
                ?>
                <div class="content">
                    <h6 class="text-center pt-2">Проверка прав на запись</h6>
                    <br>
                    <br>
                    <table class="table small table-dark">
                        <thead>
                        <tr>
                            <th scope="col" width="70%">Папка или файл</th>
                            <th scope="col" width="10%">Права</th>
                            <th scope="col" width="20%">Состояние</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <th scope="row">/system/data</th>
                            <td><?php echo substr(decoct(fileperms(dir . "/system/data")), 2); ?> </td>
                            <td><?php echo $sd; ?></td>
                        </tr>
                        </tbody>
                    </table>
                    <br>
                    Состояние проверки:<br>
                    Устанавливать скрипт <?php echo $sd; ?>
                    <br>
                    <?php if (is_writable(dir . "/system/data")) { ?>
                        <a href="<?php echo $url; ?>/install.php?step=2"
                           class="next btn btn-sm btn-success float-right mr-3">Продолжить установку</a>
                    <?php } else { ?>
                        Установите права <b>777</b> для папки <b>/system/data</b><br>
                        <br>
                        <span style="color: red; font-weight: 700;">Для продолжения установки исправте ошибку!</span>
                    <?php } ?>
                </div>
                <?php
                break;

            case 2:
                ?>
                <div class="content">
                    <h6 class="text-center pt-2">Первоначальная настройка</h6>
                    <br>
                    <br>
                    <form action="<?php echo $url; ?>/install.php?step=3" method="post">
                        IP IWEB сервера: <input class="form-control form-control-sm" type="text"
                                                name="config[server_hostname]"
                                                value="127.0.0.1"><br>
                        Версия сервера: <select class="form-control form-control-sm" name="config[version]">
                            <option value="1.5.1_101">1.5.1 (101)</option>
                            <option value="1.5.3_145">1.5.3 (145)</option>
                            <option value="1.5.5_156">1.5.5 (156)</option>
                        </select><br>
                        Имя процесса Auth: <select class="form-control form-control-sm" type="text"
                                                   name="config[serverTypeAuth]">
                            <option value="auth">auth</option>
                            <option value="authd">authd</option>
                            <option value="gauthd">gauthd</option>
                        </select><br>
                        Порт gamedbd: <input class="form-control form-control-sm" type="text" name="config[dbPort]"
                                             value="29400"><br>
                        Порт GProvider: <input class="form-control form-control-sm" type="text"
                                               name="config[GProviderPort]"
                                               value="29300"><br>
                        Порт GDeliveryd: <input class="form-control form-control-sm" type="text"
                                                name="config[gdeliverydPort]"
                                                value="29100"><br>
                        Порт glink: <input class="form-control form-control-sm" type="text" name="config[linkPort]"
                                           value="29000"><br>

                        Папка сервера: <input class="form-control form-control-sm" type="text" name="config[serverPath]"
                                              value="/home"><br>
                        Папка логов: <input class="form-control form-control-sm" type="text" name="config[logsPath]"
                                              value="/home/logs"><br>
                        <br>
                        <button type="submit" name="step2"
                                class="next btn btn-sm btn-success float-right mr-3">Продолжить установку
                        </button>
                    </form>
                </div>
                <?php
                break;

            case 3:
                $msgConfig = "";
                if (isset($_POST['step2'])) {
                    function generateRandomString($length = 34)
                    {
                        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        $charactersLength = strlen($characters);
                        $randomString = '';
                        for ($i = 0; $i < $length; $i++) {
                            $randomString .= $characters[rand(0, $charactersLength - 1)];
                        }
                        return $randomString;
                    }

                    $_SESSION['conf']['port'] = rand(1000, 99999);
                    $_SESSION['conf']['key'] = generateRandomString();

                    $configFile = '<?php
namespace system\data;
if (!defined(\'IWEB\')) {die("Error!");}
class config
{
    static $site_title = "IWEB";
    static $site_adr = "' . $url . '";
    static $site_lang = "ru";
    static $checkUpdate = "on";
    static $widgetChat = "off";
    static $countChatMsg = "50";
    static $logActions = "on";
    static $access = "off";
    static $accessIP = "";
    static $titleMail = "Подарок от GM";
    static $messageMail = "Текст сообщения";
    static $version = "' . $_POST['config']['version'] . '";
    static $dbPort = "' . $_POST['config']['dbPort'] . '";
    static $gdeliverydPort = "' . $_POST['config']['gdeliverydPort'] . '";
    static $GProviderPort = "' . $_POST['config']['GProviderPort'] . '";
    static $linkPort = "' . $_POST['config']['linkPort'] . '";
    static $serverPath = "' . $_POST['config']['serverPath'] . '";
    static $logsPath = "' . $_POST['config']['logsPath'] . '";
    static $serverTypeAuth = "auth";
    static $chatFile = "world2.chat";
    static $server_hostname = "' . $_POST['config']['server_hostname'] . '";
    static $server_port = "' . $_SESSION['conf']['port'] . '";
    static $server_key = "' . $_SESSION['conf']['key'] . '";
}';

                    $fw = fopen(dir . "/system/data/config.php", "w");
                    if (!fwrite($fw, $configFile)) {
                        $msgConfig = "Не удалось записать настройки в файл!";
                    }
                    fclose($fw);
                }
                ?>
                <div class="content">
                    <h6 class="text-center pt-2">Настройки базы данных</h6>
                    <br>
                    <br>
                    <?php if (empty($msgConfig)) { ?>
                        <form action="<?php echo $url; ?>/install.php?step=4" method="post">
                            IP сервера: <input class="form-control form-control-sm" type="text" name="dbconfig[host]"
                                               value="127.0.0.1"><br>
                            Имя пользователя: <input class="form-control form-control-sm" type="text"
                                                     name="dbconfig[user]"
                                                     value="root"><br>
                            Пароль: <input class="form-control form-control-sm" type="password"
                                           name="dbconfig[password]"
                                           value=""><br>
                            Имя базы данных: <input class="form-control form-control-sm" type="text"
                                                    name="dbconfig[table]"
                                                    value="iweb"><br>
                            <br>
                            <button type="submit" name="step3"
                                    class="next btn btn-sm btn-success float-right mr-3">Продолжить установку
                            </button>
                        </form>
                    <?php } else {
                        echo $msgConfig;
                    }
                    ?>
                    <br>


                </div>
                <?php
                break;

            case 4:
                if (isset($_POST['step3'])) {
                    $configFile = '<?php
namespace system\data;
if (!defined(\'IWEB\')) {die("Error!");}
class dbconfig
{
    static $host = "' . $_POST['dbconfig']['host'] . '";
    static $user = "' . $_POST['dbconfig']['user'] . '";
    static $password = "' . $_POST['dbconfig']['password'] . '";
    static $table = "' . $_POST['dbconfig']['table'] . '";
    static $charset = "utf8";
}';

                    $_SESSION['db']['host'] = $_POST['dbconfig']['host'];
                    $_SESSION['db']['user'] = $_POST['dbconfig']['user'];
                    $_SESSION['db']['password'] = $_POST['dbconfig']['password'];
                    $_SESSION['db']['table'] = $_POST['dbconfig']['table'];

                    $fw = fopen(dir . "/system/data/dbconfig.php", "w");
                    if (!fwrite($fw, $configFile)) {
                        $msgConfig = "Не удалось записать настройки в файл!";
                    }
                    fclose($fw);

                    $msgDB='';
                    $db = @new mysqli($_SESSION['db']['host'], $_SESSION['db']['user'],$_SESSION['db']['password']);
                    if($db){
                        if (!$db->query("USE " . $_SESSION['db']['table'])) {
                            if ($db->query("CREATE DATABASE " . $_SESSION['db']['table'])) {
                                $db->query("USE " . $_SESSION['db']['table']);
                                $db->set_charset("utf8");
                                $db->query("CREATE TABLE `groups` (
                                                  `id_group` int(11) NOT NULL,
                                                  `title` varchar(255) DEFAULT NULL,
                                                  `xml_edit` tinyint(1) DEFAULT NULL,
                                                  `visual_edit` tinyint(1) DEFAULT NULL,
                                                  `gm_manager` tinyint(1) DEFAULT NULL,
                                                  `kick_role` int(1) DEFAULT NULL,
                                                  `ban` int(1) DEFAULT NULL,
                                                  `add_gold` int(1) DEFAULT NULL,
                                                  `level_up` int(1) DEFAULT NULL,
                                                  `rename_role` int(1) DEFAULT NULL,
                                                  `teleport` int(1) DEFAULT NULL,
                                                  `null_exp_sp` int(1) DEFAULT NULL,
                                                  `del_role` int(1) DEFAULT NULL,
                                                  `server_manager` int(1) DEFAULT NULL,
                                                  `send_msg` int(1) DEFAULT NULL,
                                                  `send_mail` int(1) DEFAULT NULL,
                                                  `settings` int(1) DEFAULT NULL,
                                                  `logs` int(1) DEFAULT NULL
                                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

                                $db->query("INSERT INTO `groups` (`id_group`, `title`, `xml_edit`, `visual_edit`, `gm_manager`, `kick_role`, `ban`, `add_gold`, `level_up`, `rename_role`, `teleport`, `null_exp_sp`, `del_role`, `server_manager`, `send_msg`, `send_mail`, `settings`, `logs`) VALUES
(1, 'Главный администратор', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);");

                                $db->query("CREATE TABLE `logs` (
                                                  `id` int(11) NOT NULL,
                                                  `ip` varchar(255) NOT NULL,
                                                  `date` int(11) NOT NULL,
                                                  `user` varchar(255) NOT NULL,
                                                  `action` text NOT NULL
                                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

                                $db->query("CREATE TABLE `mail` (
                                                  `idMail` int(11) NOT NULL,
                                                  `titleItem` text NOT NULL,
                                                  `messageItem` text NOT NULL,
                                                  `idItem` int(11) NOT NULL,
                                                  `countItem` int(11) NOT NULL,
                                                  `maxCountItem` int(11) NOT NULL,
                                                  `octetItem` text NOT NULL,
                                                  `prototypeItem` int(11) NOT NULL,
                                                  `timeItem` int(11) NOT NULL,
                                                  `maskItem` int(11) NOT NULL,
                                                  `moneyItem` int(11) NOT NULL
                                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

                                $db->query("CREATE TABLE `users` (
                                                  `id` int(11) NOT NULL,
                                                  `name` varchar(255) NOT NULL,
                                                  `password` varchar(255) NOT NULL,
                                                  `group_id` int(11) NOT NULL
                                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

                                $db->query("ALTER TABLE `groups` ADD PRIMARY KEY (`id_group`);");
                                $db->query("ALTER TABLE `logs` ADD PRIMARY KEY (`id`);");
                                $db->query("ALTER TABLE `mail` ADD PRIMARY KEY (`idMail`);");
                                $db->query("ALTER TABLE `users` ADD PRIMARY KEY (`id`);");
                                $db->query("ALTER TABLE `groups` MODIFY `id_group` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;");
                                $db->query("ALTER TABLE `logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=333;");
                                $db->query("ALTER TABLE `mail` MODIFY `idMail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;");
                                $db->query("ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;");
                                //$db->query("");
                            }else{
                                $msgDB = "Невозможно создать базу данный: <b>{$_SESSION['db']['table']}</b>.";
                            }
                        }else{
                            $msgDB = "База данный: <b>{$_SESSION['db']['table']}</b> уже существует.";
                        }
                    }else{
                        $msgDB = "Не удалось установить соединение с базой данных";
                    }
                    $db->close();

                }
                ?>
                <div class="content">
                    <h6 class="text-center pt-2">Добавление нового пользователя</h6>
                    <br>
                    <br>
                    <?php if(empty($msgDB)){?>
                    <form action="<?php echo $url; ?>/install.php?step=5" method="post">
                        Имя пользователя: <input class="form-control form-control-sm" type="text"
                                                 name="user"
                                                 value=""><br>
                        Пароль: <input class="form-control form-control-sm" type="password"
                                       name="password"
                                       value=""><br>
                        <br>
                        <button type="submit" name="step4"
                                class="next btn btn-sm btn-success float-right mr-3">Продолжить установку
                        </button>
                    </form>
                    <?php }else{ echo $msgDB; }?>
                </div>
                <?php
                break;

            case 5:
        if (isset($_POST['step4'])) {
            $msgDB='';
            $db = @new mysqli($_SESSION['db']['host'], $_SESSION['db']['user'],$_SESSION['db']['password'], $_SESSION['db']['table']);
            if($db){
                if ($db->query("INSERT INTO `users` (`id`, `name`, `password`, `group_id`) VALUES
(NULL, '".$_POST['user']."', '".md5($_POST['password'])."', 1);")){

                }else{
                    $msgDB = "Не удалось создать аккаунт";
                }
            }else{
                $msgDB = "Не удалось установить соединение с базой данных";
            }

        }
                ?>
                <div class="content">
                    <h6 class="text-center pt-2">Завершение установки</h6>
                    <br>
                    <br>
                    <?php if(empty($msgDB)){?>
                        Установка прошла успешно!<br>
                        Теперь измените настройки сервера и запустите его.<br>
                        Порт: <b><?php echo $_SESSION['conf']['port'];?></b><br>
                        Ключ: <b><?php echo $_SESSION['conf']['key'];?></b><br>
                        <a href="<?php echo $url; ?>"
                           class="next btn btn-sm btn-success float-right mr-3">Завершить</a>
                    <?php }else{ echo $msgDB; }?>

                </div>
                <?php break;

            default:
                ?>
                <div class="content">
                    <h6 class="text-center pt-2">Скрипт автоматической установки IWEB</h6>
                    <br>
                    <br>
                    Добро пожаловать в скрипт по установке IWEB. <br>Данный скрипт поможет вам установить систему за
                    считанные минуты.<br>
                    Однако, при возникновении ошибок в работе или установке IWEB, мы настоятельно рекомендуем
                    обратиться в тех. поддержку на нашем сайте.
                    <br><br>
                    Прежде чем начать установку убедитесь, что все файлы IWEB загруженны на сервер.
                    <br><br>
                    <hr>
                    <a href="<?php echo $url; ?>/install.php?step=1"
                       class="next btn btn-sm btn-success float-right mr-3">Начать установку</a>

                </div>
                <?php
                break;
        }

        ?>
    </div>

</div>
<script href="<?php echo $url; ?>/system/template/js/jquery-3.2.1.min.js"></script>
<script href="<?php echo $url; ?>/system/template/js/bootstrap.min.js"></script>
</body>
</html>
