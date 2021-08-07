<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>
        Password Recovery
    </title>
</head>
<body>
    <p>Hi, $Member.FirstName</p>
    <p>Please use the link below to reset your password: </p>
    <p><a href="{$baseURL}password-recovery?id={$Member.ID}&token={$Member.ValidationKey}">{$baseURL}password-recovery?id={$Member.ID}&token={$Member.ValidationKey}</a></p>
</body>
</html>
