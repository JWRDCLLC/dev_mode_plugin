/**
 * Dev Mode Settings Page Tab Switching
 */
(function($) {
    'use strict';

    $(document).on('click', '.dev-mode-tabs .nav-tab', function(e) {
        e.preventDefault();

        var $tab = $(this);
        var target = $tab.data('tab');

        $tab.siblings('.nav-tab').removeClass('nav-tab-active');
        $tab.addClass('nav-tab-active');

        $('.dev-mode-tab-panel').attr('hidden', true);
        $('.dev-mode-tab-panel[data-tab-panel="' + target + '"]').removeAttr('hidden');
    });

})(jQuery);
