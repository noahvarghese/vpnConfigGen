<?php

require_once('config.php');

session_start();

$_SESSION['email'] = ! empty($_SESSION['email']) ? $_SESSION['email'] : "";
$_SESSION['errors'] = "";
$_SESSION['download'] = "";

exec("sudo rm -f /var/www/html/vpn/*");

if ( $_SERVER['REQUEST_METHOD'] == "POST" )
{
	if ( $_POST['login'] )
	{
		$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
			
		if ( ! empty($email) && ! empty($_POST['password'] ) )
		{
			$stmt = $dbh->prepare("SELECT password FROM login WHERE email = ?");
			$stmt->execute([$email]);
			$result = $stmt->fetch();

			if ( password_verify($password, $result['password'] ) )
			{
				$_SESSION['email'] = $email;
			}	
			else
			{
				$_SESSION['errors'] = "Incorrect username/password.\n";
			}
		}
		else
		{
			$_SESSION['errors'] = "Username/password field cannot be left blank.";
		}
	}
	if ( $_POST['vpn'] )
	{
		$name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
		$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
		$phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
		$device = filter_var($_POST['device'], FILTER_SANITIZE_STRING);

		$data = [
			$name,
			$email,
			$phone,
			$device
		];
		
		if ( $email === $_SESSION['email'] )
		{
			$empty = 0;

			foreach ( $data as $item )
			{
				empty($item) ? $empty +=1 : $empty = $empty;
			}
			
			if ( $empty == 0 )
			{
				$stmt = $dbh->prepare("SELECT id FROM vpn_users WHERE email = ? AND device = ?");
				$stmt->execute([$email, $device]);
				$result = $stmt->fetch();
				$deviceID = $result['id'];
	
				if ( $deviceID )
				{
					$_SESSION['errors'] = "Device already exists for user.";
				}
				else
				{
					$stmt = $dbh->prepare("INSERT INTO vpn_users (name, email, phone, device) VALUES (?,?,?,?)");
					$stmt->execute($data);

					$id = $dbh->lastInsertId();

					if ( $id )
					{
						$fileName = str_replace(' ', '', $name) . "-$device";

						$cmd = "sudo /var/www/html/vpn.sh $fileName";

						$result = exec($cmd);

						if ( ! $result ) 
						{
							$_SESSION['errors'] = "There was an error running the script.";
						}
						else
						{
							$file = "http://" . $_SERVER['SERVER_NAME'] . "/vpn/$fileName.zip";
							$_SESSION['download'] = $file;
						}
					}
					else
					{
						$_SESSION['errors'] = "There was an error adding your credentials.";
					}
				}
			}
		}
		else
		{
			$_SESSION['errors'] = "Email provided does not match current user.";
		}
	}

}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>VPN Config</title>
		<meta charset="utf-8">
		<style>
			a {
				text-decoration: none;
			}
			a > button {
				text-decoration: none;
				color: white;
				font-size: 1.25em;
				font-weight: bold;
				display: block;
				text-align: center;
				background-color: #00acee;
				border-radius: 5vw;
				margin: 2vh 0vw 2vh 0vw;
				border: solid #00acee 2px;
				outline: none;
				align-items: flex-start;
			}
			button:hover {
				background-color: #1da1f2;
			}
			button active {
				background-color: #1da1f2;
				transform: translateY(4px);
			}
			h1 {
				text-align: center;
			}
			.visible {
				display: block;
			}
			.hidden {
				display: none;
			}
			#errors {
				color: white;
				background-color: red;
				height: 5vh;
				font-size: 1.25em;
				text-align: center;
				border-radius: 5vw;
				font-weight: 900;
				font-family: Arial;
				border: solid red 2px;
			}
			div {
				margin: 0.5em 0em 0.5em 5vw;
			}
			label {
				display: inline-block;
			}
			@media only screen and (max-width: 1024px) {
				h1 {
					font-size: 3em;
				}
				a > button {
					font-size: 3em;
					width: 60vw;
					height: 10vh;
					margin-left: 8vw;
				}
				#errors {
					font-size: 2em;
					width: 60vw;
					margin-left: 10vw;
				}
				body {
					width: 80vw;
					margin-left: 10vw;
				}
				form {
					font-size: 2em;
				}
				label {
					width: 30vw;
				}
				input:not([type='submit']) {
					width: 30vw;
					height: 2em;
					border: solid black 2px;
				}
				input[type='submit'] {
					width: 30vw;
					height: 2em;
					font-size: 0.75em;
					margin-left: 22vw;
					margin-top: 2vh;
				}
			}
			@media only screen and (min-width: 1025px) {
				#errors {
					width: 20vw;
					margin-left: 6.5vw;
				}
				a > button {
					margin-left: 1.5vw;
					width: 20vw;
					height: 5vh;
				}
				body {
					width: 33vw;
					margin-left: 33vw;
				}
				form {
					font-size: 1.25em;
				}
				div {
					width: 25vw;
				}
				label {
					width: 10vw;
				}
				input {
					width: 10vw;
					height: 1.5em;
				}
				input[type='submit'] {
					height: 2em;
					width: 10vw;
					margin-left: 10vw;
				}
			}
		</style>
	</head>
	<body>
		<h1>VPN Config</h1>
		<div id="errors" class="<?= $_SESSION['errors'] ? "visible" : "hidden" ?>">
			<?= $_SESSION['errors'] ?>
		</div>
		<div id="download" class="<?= $_SESSION['download'] ? "visible" : "hidden"  ?>">
			<a href="<?= $_SESSION['download'] ?>"><button>Click Here to Download</button></a>
		</div>
		<?php
		if ( ! empty( $_SESSION['email'] ) )
		{ 
		?>	
			<form method="POST" action="">
				<fieldset>
					<legend>Credentials</legend>
					<div>
						<label for="name">Name:</label>
						<input type="text" id="name" name="name" autocomplete="name" required>
					</div>
					<div>
						<label for="email">Email:</label>
						<input type="email" name="email" id="email" autocomplete="email" required>
					</div>
					<div>
						<label for="phone">Phone:</label>
						<input type="tel" id="phone" name="phone" autocomplete="tel" required>
					</div>
					<div>
						<label for="device">Device Name:</label>
						<input type="text" name="device" id="device" required>
					</div>
					<input type="hidden" name="vpn" value="true">
					<input type="submit" value="Submit">
				</fieldset>
			</form>
		<?php
		} 
		else 
		{ 
		?>
			<form method="POST" action="">		
				<fieldset>
					<legend>Login</legend>
					<div>
						<label for="email">Email:</label>
						<input type="email" name="email" id="email" autocomplete="email" required>
					</div>
					<div>
						<label for="password">Password:</label>
						<input type="password" id="password" name="password" autocomplete="password" required>
					</div>
					<input type="hidden" name="login" value="true">
					<input type="submit" value="Login">
				</fieldset>
			</form>
		<?php
		}
		?>
	</body>
</html>
