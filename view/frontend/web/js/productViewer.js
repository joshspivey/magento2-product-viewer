define([
    "underscore",
    "jquery",
    "jquery/ui"

], function(
    _, $
) {

    var ProductViewer = function(){};

    ProductViewer.prototype.init = function(config) {
        this.config = config;
        this.setupEvents();
        this.buildUI();
    };

    ProductViewer.prototype.setupEvents = function() {
        var self = this;

        $("#"+this.config.button).on('click', function(evt){
            evt.preventDefault();
            self.loadProducts();
        });

        $("#"+this.config.low).on('change', function(evt){
            console.log($(evt.target).val());
        });

        $("#"+this.config.high).on('change', function(evt){
            console.log($(evt.target).val());
        });
    };

    ProductViewer.prototype.validate = function() {


    };


    ProductViewer.prototype.buildUI = function() {
        var self = this,
            low = $("#"+this.config.low).val(),
            high = $("#"+this.config.high).val();


        $("#"+this.config.slider).slider({
            range: true,
            min: low,
            max: high,
            values: [low+20, high-20],
            slide: function(event, ui) {
                $("#"+self.config.amount).val("$" + ui.values[0] + " - $" + ui.values[1]);
            }
        });
        $("#"+this.config.amount).val("$" + $("#"+this.config.slider).slider("values", 0) +
            " - $" + $("#"+this.config.slider).slider("values", 1));
    };

    ProductViewer.prototype.destroySlider = function() {
        $("#"+this.config.slider).slider("destroy");
    };

    ProductViewer.prototype.loadProducts = function() {
        var self = this;

        $.ajax({ 
            type: 'GET', 
            url: '/productviewer/products/products', 
            data: { results_per_page: 10, page: 1 }, 
            dataType: 'json',
            success: function (data) { 
                self.renderProducts(data);
            }
        });
    };


     ProductViewer.prototype.renderProducts = function(data) {
        var self = this;
        $('#'+self.config.products).html("");
        _.each(data, function(item) {
           var tpl = _.template($('#'+self.config.productTPL).html());
           $('#'+self.config.products).append(tpl(item));
        });


     };

    return ProductViewer;

});