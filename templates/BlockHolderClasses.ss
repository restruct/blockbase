block-item-holder block-outer
<% if $LinkedElementID %>
block-outer_{$LinkedElement.ClassName.ShortName} {$LinkedElement.Style} {$LinkedElement.ExtraClass}
<% else %>
block-outer_{$ClassName.ShortName} $Style $ExtraClass
<% end_if %>
<% if not $Element.ID %>
pseudo-block pseudo-block-item_{$ClassName.ShortName} pseudo-block-item_id_{$ID}
<% else %>
real-block block-item_{$ClassName.ShortName}
<% end_if %>
