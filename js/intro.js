(function ($, document) {
    $(document).ready(function () {
        var option = $('#toplevel_page_yumprint_recipe_themes');
        var element = $("<div class='yumprint-recipe-intro yumprint-recipe-left'><div class='yumprint-recipe-arrow'></div><div class='yumprint-recipe-container'><div class='yumprint-recipe-text'>Click here to create your recipe template</div><div class='yumprint-recipe-skip'>Dismiss</div></div></div>");

        var position = option.offset();

        element.css(position).appendTo("body");

        element.find(".yumprint-recipe-skip").click(function () {
            element.remove();
        });

        element.css("opacity", 0).animate({
            "opacity": 1
        }, 500);
    });
})(jQuery, document);
