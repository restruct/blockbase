<div class="$SiteConfig.ContainerClass">
    <div class="row">

        <% if $StyleVariant="one-two-thirds-layout" %>
            <div class="col-lg-4 typography">
                <% if $Heading %><h2>{$Heading}</h2><% end_if %>
            </div>
            <div class="col-lg-8 typography">
                $Content
            </div>
        <% else %>
            <div class="col typography">
                <% if $Heading %><h2>{$Heading}</h2><% end_if %>
                <% if $Page.ClassName.ShortName=='NewsGridPage' %><h6 class="text-muted font-italic font-weight-normal">$Page.Date.Format(dd-MM-y)</h6><% end_if %>

                $Content

                <% if $Page.ClassName.ShortName=='NewsGridPage' %><div class="newsuplink"><a href="$Page.Parent.Link">&lt; $Page.Parent.MenuTitle</a></div><% end_if %>
            </div>
        <% end_if %>

    </div>
</div>
