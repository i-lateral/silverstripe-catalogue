<% include SideBar %>

<div class="catalogue-content-container typography <% if $Menu(2) %>unit size3of4 unit-75<% end_if %>">
    <% if Level(2) %><p>$Breadcrumbs</p><% end_if %>

    <h1>$Title</h1>

    <div class="units-row catalogue-list">
        <% if $Children.exists %>
            <p>
                <strong><%t Catalogue.FilterBy "Filter by" %>:</strong>
                <% loop $Children %>
                    <span class="link"><a href="$Link">$Title</a></span>
                    <% if not $Last %>|<% end_if %>
                <% end_loop %>
            </p>
        <% end_if %>
    </div>

    <% if $PaginatedAllProducts(8).exists %>
        <div class="units-row line catalogue-list">
            <% loop $PaginatedAllProducts(8) %>
                <div class="unit-25 unit size1of4 catalogue-list-child">
                    <h2><a href="$Link">$Title</a></h2>

                    <p>
                        <a href="$Link">$SortedImages.First.CroppedImage(180,180)</a>

                        <span class="price label label-green big">                            
                            <% if $IncludesTax %>
                                {$PriceAndTax.nice}
                            <% else %>
                                {$Price.nice}
                            <% end_if %>
                        </span>
                        
                        <% if TaxString %>
                            <span class="tax"> 
                                {$TaxString}
                            </span>
                        <% end_if %>
                    </p>
                </div>

                <% if $MultipleOf(4) %></div><div class="units-row line catalogue-list"><% end_if %>
            <% end_loop %>
        </div>

        <% with $PaginatedAllProducts(8) %>
            <% if $MoreThanOnePage %>
                <ul class="pagination line">
                    <% if $NotFirstPage %>
                        <li class="prev unit">
                            <a class="prev" href="$PrevLink">Prev</a>
                        </li>
                    <% end_if %>

                    <% loop $Pages %>
                        <% if $CurrentBool %>
                            <li class="unit"><span>$PageNum</span></li>
                        <% else %>
                            <% if $Link %>
                                <li class="unit"><a href="$Link">$PageNum</a></li>
                            <% else %>
                                <li class="unit">...</li>
                            <% end_if %>
                        <% end_if %>
                    <% end_loop %>

                    <% if $NotLastPage %>
                        <li class="unit next">
                            <a class="next" href="$NextLink">Next</a>
                        </li>
                    <% end_if %>
                </ul>
            <% end_if %>
        <% end_with %>

    <% end_if %>
</div>
