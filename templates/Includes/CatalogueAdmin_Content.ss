<div class="cms-content cms-tabset center $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<div class="breadcrumbs-wrapper" <% if $Breadcrumbs.exists && $Breadcrumbs.count > 1 %>data-pjax-fragment="Breadcrumbs"<% end_if %>>
				<h2 id="page-title-heading">
                    <% if $Breadcrumbs.exists && $Breadcrumbs.count > 1 %>
                        <% loop $Breadcrumbs %>
                            <% if not $Last %>
                                <a class="cms-panel-link crumb" href="$Link">$Title</a>
                                <span class="sep">/</span>
                            <% end_if %>
                        <% end_loop %>
                    <% end_if %>
					<span class="cms-panel-link crumb last">
                        <% if $Breadcrumbs.exists %>
                            $Breadcrumbs.last.Title
						<% else_if $SectionTitle %>
							$SectionTitle
						<% else %>
							<%t ModelAdmin.Title 'Data Models' %>
						<% end_if %>
					</span>
				</h2>
			</div>
		</div>

		<div class="cms-content-header-tabs cms-tabset-nav-primary ss-ui-tabs-nav">
			<button id="filters-button" class="icon-button font-icon-search no-text" title="<%t CMSPagesController_Tools_ss.FILTER 'Filter' %>"></button>
			<ul class="cms-tabset-nav-primary">
				<% loop $ManagedModelTabs %>
				<li class="tab-$ClassName $LinkOrCurrent<% if $LinkOrCurrent == 'current' %> ui-tabs-active<% end_if %>">
					<a href="$Link" class="cms-panel-link" title="Form_EditForm">$Title</a>
				</li>
				<% end_loop %>
			</ul>
		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content cms-panel-padded" data-layout-type="border">
		$Tools

		<div class="cms-content-view">
			$EditForm
		</div>
	</div>
	
</div>
