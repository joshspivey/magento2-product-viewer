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
            self.validate();
        });

        $("#"+this.config.high).on('change', function(evt){
            self.validate();
        });
    };

    ProductViewer.prototype.validate = function(cb = function(){}) {

        if($("#"+this.config.low).val() < 0){
            cb('error', 'Please select a number higher than 0.');
        }else if($("#"+this.config.high).val() - $("#"+this.config.low).val() < 500){
            cb('error', 'The value must be more then 500 of the starting value.');
        }else{
            cb('success', '');
        }
        
    };


    ProductViewer.prototype.buildUI = function() {
        var self = this,
            low = $("#"+this.config.low).val(),
            high = $("#"+this.config.high).val();


        $("#"+this.config.slider).slider({
            range: true,
            min: 0,
            max: 1000,
            minRange: 500,
            step: 1,
            values: [low, high],
            slide: function(event, ui) {
                if(ui.values[1] - ui.values[0] <= 500){
                    // do not allow change
                    ui.values[1] = ui.values[0] + 500;
                    return false;
                } else {
                    // allow change
                    $("#"+self.config.amount).val("$" + ui.values[0] + " - $" + ui.values[1]); 
                }   
                
            },
            stop: function(event, ui){
                $("#"+self.config.low).val(ui.values[0]);
                $("#"+self.config.high).val(ui.values[1]);
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
        this.validate(function(type, msg){
            if(type === "error"){
                alert(msg);
            }
            if(type === "success"){
                $.ajax({ 
                    type: 'GET', 
                    url: '/productviewer/products/products', 
                    data: { 
                        results_per_page: 10, 
                        page: 1, 
                        order: $('#sort').val(), 
                        low: $("#"+self.config.low).val(),
                        high: $("#"+self.config.high).val() 
                    }, 
                    dataType: 'json',
                    success: function (data) { 
                        if(typeof data === 'string'){
                            alert(data);
                        }else{
                            self.renderProducts(data);
                        }
                    }
                });
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