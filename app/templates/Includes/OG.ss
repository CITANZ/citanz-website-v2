<% with $OGTwitter %>
<% if $OGType %>
<meta property="og:type" content="$OGType" />
<meta property="og:url" content="$AbsoluteLink" />
<% end_if %>
<% if OGTitle %><meta property="og:title" content="$OGTitle" /><% end_if %>
<% if OGDescription %><meta property="og:description" content="$OGDescription" /><% end_if %>
<% if OGImage %>
<meta property="og:image" content="$OGImage.Cropped.FillMax(300, 300).AbsoluteURL" />
<meta property="og:image:width" content="300" />
<meta property="og:image:height" content="300" />
<% end_if %>
<% if OGImageLarge %>
<meta property="og:image" content="$OGImageLarge.Cropped.FillMax(1200, 630).AbsoluteURL" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<% end_if %>
<% if TwitterCard %><meta name="twitter:card" content="$TwitterCard" /><% end_if %>
<% if TwitterTitle %><meta name="twitter:title" content="$TwitterTitle" /><% end_if %>
<% if TwitterDescription %><meta name="twitter:description" content="$TwitterDescription" /><% end_if %>
<% if TwitterImage %>
<% if TwitterCard == 'summary_large_image' %>
<meta name="twitter:image" content="$TwitterImageLarge.Cropped.FillMax(600, 300).AbsoluteURL" />
<% else %>
<meta name="twitter:image" content="$TwitterImage.Cropped.FillMax(300, 300).AbsoluteURL" />
<% end_if %>
<% end_if %>
<% end_with %>
