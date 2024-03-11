<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>
        Job application notification
    </title>
</head>
<body>
    <p><strong>Applying for</strong>: <a href="https://www.cita.org.nz/referral-opportunities/view/referral-opportunity-{$Application.Job.ID}" target="_blank">$Application.Job.Title</a></p>
    <p><strong>Applicant</strong>: $Application.FirstName $Application.LastName</p>
    <p><strong>Email</strong>: $Application.Email</p>
    <% if $Application.Phone %>
    <p><strong>Phone</strong>: $Application.Phone</p>
    <% end_if %>
    <% if $Application.LinkedIn %>
    <p><strong>LinkedIn</strong>: $Application.LinkedIn</p>
    <% end_if %>
    <% if $Application.Github %>
    <p><strong>Github ID</strong>: $Application.Github</p>
    <% end_if %>
    <% if $Application.WechatID %>
    <p><strong>Wechat ID</strong>: $Application.WechatID</p>
    <% end_if %>
</body>
</html>
