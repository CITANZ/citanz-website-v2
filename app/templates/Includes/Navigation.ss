<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <%-- uncomment below if nav should be wrapped in a container - and also uncomment the 42nd line --%>
    <div class="container">
        <div class="navbar-brand">
            <a class="navbar-brand" href="/" id="logo" rel="start">
            <% if $SiteConfig.SiteLogo %>
                <% with $SiteConfig.SiteLogo.SetHeight(80) %>
                <img alt="$Up.Up.Title" width="$Width" height="$Height" src="$URL" />
                <% end_with %>
            <% else %>
                $SiteConfig.Title
            <% end_if %>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div id="main-nav" class="navbar-menu">
            <div class="navbar-end">
                <%-- Uncomment below --%>
                <%-- <% loop $MenuSet('Main Menu').MenuItems %> --%>
                <%-- and comment the NEXT LINE (literally, the one below) if using menu manager --%>
                <% loop Menu(1) %>
                <% if $Children %>
                <div class="navbar-item has-dropdown is-hoverable">
                    <a class="navbar-link<% if LinkOrCurrent = current || $LinkOrSection = section %> is-active<% end_if %>" href="$Link">$MenuTitle.XML</a>
                    <div class="navbar-dropdown ">
                        <% loop Children %>
                            <a class="navbar-item<% if LinkOrCurrent = current %> is-active<% end_if %>" href="$Link">$MenuTitle.XML</a>
                        <% end_loop %>
                    </div>
                </div>
                <% else %>
                <a class="navbar-item<% if LinkOrCurrent = current || $LinkOrSection = section %> is-active<% end_if %>" href="$Link">$MenuTitle.XML</a>
                <% end_if %>
                <% end_loop %>
            </div>
        </div>
    </div>
</nav>
