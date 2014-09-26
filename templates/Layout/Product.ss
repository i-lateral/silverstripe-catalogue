<div class="catalogue-content-container catalogue-product typography">
    <p>$Breadcrumbs</p>

    <h1>$Title</h1>

    <div class="units-row line">
        <div class="unit-50 unit size1of2 catalogue-product-images">
            <div id="catalogue-product-image">
                <a href="{$ProductImage.SetRatioSize(900,550).URL}">
                    $ProductImage.PaddedImage(500,500)
                </a>
            </div>

            <div class="units-row-end">
                <% if $Images.exists %>
                    <div class="thumbs">
                        <% loop $SortedImages %>
                            <a href="{$Top.Link('image')}/$ID#catalogue-product-image">
                                $PaddedImage(75,75)
                            </a>
                        <% end_loop %>
                    </div>
                <% end_if %>
            </div>
        </div>

        <div class="unit-50 unit size1of2 catalogue-product-summary">
            <p>
                <span class="price label big label-green">
                    <span class="title"><% _t('Catalogue.Price','Price') %>:</span>
                    <span class="value">
                        <% if $IncludesTax %>
                            {$PriceAndTax.nice}
                        <% else %>
                            {$Price.nice}
                        <% end_if %>
                    </span>
                </span>
                <span class="tax"> 
                    <% if TaxString %>
                        <span class="tax"> 
                            {$TaxString}
                        </span>
                    <% end_if %>
                </span>
            </p>

            <div class="content">
                $Content
            </div>
            
            <div class="form">
                $Form
            </div>
        </div>
    </div>

    <%-- Related Products: Only loaded when added through the CMS --%>
    <% if $RelatedProducts.exists %>
        <hr/>

        <h2><% _t('Catalogue.RelatedProducts','Related Products') %></h2>

        <div class="units-row catalogue-related-products line">
            <% loop $RelatedProducts %>
                <div class="unit-25 unit size1of4 catalogue-list-item">
                    <h3><a href="$Link">$Title</a></h3>

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

                <% if $MultipleOf(5) && not $Last %>
                    </div><div class="units-row catalogue-related-products line">
                <% end_if %>
            <% end_loop %>
        </div>
    <% end_if %>
</div>

<% include SideBar %>
