(function ($) {
  'use strict';

  $(document).on('click', '.ae-select-media', function (event) {
    event.preventDefault();
    const $input = $(this).closest('.ae-media-row').find('.ae-media-id');
    const frame = wp.media({ title: 'Choose media', button: { text: 'Use media' }, multiple: false });
    frame.on('select', function () {
      const item = frame.state().get('selection').first().toJSON();
      $input.val(item.id);
    });
    frame.open();
  });

  $(document).on('click', '.ae-clear-media', function (event) {
    event.preventDefault();
    $(this).closest('.ae-media-row').find('.ae-media-id').val('');
  });

  $(document).on('click', '.ae-select-gallery', function (event) {
    event.preventDefault();
    const $input = $(this).closest('.ae-media-row').find('.ae-gallery-ids');
    const frame = wp.media({ title: 'Choose gallery', button: { text: 'Use gallery' }, multiple: true });
    frame.on('select', function () {
      const ids = frame.state().get('selection').map(function (item) { return item.toJSON().id; });
      $input.val(ids.join(','));
    });
    frame.open();
  });

  $(document).on('click', '.ae-clear-gallery', function (event) {
    event.preventDefault();
    $(this).closest('.ae-media-row').find('.ae-gallery-ids').val('');
  });
})(jQuery);
