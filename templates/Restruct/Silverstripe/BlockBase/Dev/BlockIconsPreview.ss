<!DOCTYPE html>
<html>
<head>
	<% base_tag %>
	<title>Block-Type Icons</title>
	<style>
	    .element-editor-header__info.last {
	        border-bottom: 1px solid grey;
	    }
	</style>
</head>
<body>
<div class="container">
    <h2 class="row col"><br /><br />BlockTypes:</h2>
    <% loop $BlockTypes %>
    <div class="row col">
        <br /><br />
        <div class="element-editor-header__info $LastItem" style="border-top: 1px solid grey; padding: 1rem 0;">
            <div class="element-editor-header__icon-container">$Icon</div>
            <h3 class="element-editor-header__title"><strong>$Description</strong><br /><code>$ClassName</code></h3>
            <hr />
            <button type="button" class="{$IconClass} btn--icon-xl element-editor-add-element__button popover-option-set__button btn btn-secondary">$Description</button>
        </div>
        <br /><br />
    </div>
    <% end_loop %>
</div>
</body>
</html>
