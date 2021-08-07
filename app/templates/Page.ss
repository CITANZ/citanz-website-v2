<!DOCTYPE html>
<!--[if !IE]><!-->
<html lang="$ContentLocale">
<!--<![endif]-->
<!--[if IE 6 ]><html lang="$ContentLocale" class="ie ie6"><![endif]-->
<!--[if IE 7 ]><html lang="$ContentLocale" class="ie ie7"><![endif]-->
<!--[if IE 8 ]><html lang="$ContentLocale" class="ie ie8"><![endif]-->
<head>
    $SiteConfig.GoogleSiteVerificationCode.RAW
    <% base_tag %>
    <title><% if $URLSegment == 'home' %><% if $MetaTitle %>$MetaTitle<% else %>$SiteConfig.Title<% end_if %><% else %><% if $MetaTitle %>$MetaTitle<% else %>$Title<% end_if %><% end_if %></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    $MetaTags(false)
    <% include OG %>
    $SiteConfig.GoogleAnalyticsCode.RAW
    $SiteConfig.GTMHead.RAW
    <% if $InitialPageData %>
    <script>
        window.appInitialData = $InitialPageData.RAW;
    </script>
    <% end_if %>
    <style>
        #app {
            opacity: 0;
            visibility: hidden;
        }
    </style>
</head>
<body <% if $i18nScriptDirection %>dir="$i18nScriptDirection"<% end_if %>>
$SiteConfig.GTMBody.RAW
<% if $URLSegment == 'Security' %>
<main id="main" class="main">
    $Layout
</main>
<% else %>
<div id="app">
    <% include Header %>
    <main id="main" class="main">
        $Layout
    </main>
    <% include Footer %>
</div>
<% end_if %>
</body>
</html>
