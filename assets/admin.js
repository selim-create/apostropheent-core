(function ($) {
  'use strict';

  function previewItem(item) {
    const thumb = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url;
    return '<div class="ae-preview-item"><img class="ae-preview-image" src="' + thumb + '" alt=""><span>#' + item.id + '</span></div>';
  }

  $(document).on('click', '.ae-select-media', function (event) {
    event.preventDefault();
    const $field = $(this).closest('.ae-field-control');
    const $input = $field.find('.ae-media-id');
    const $preview = $field.find('.ae-media-preview');
    const frame = wp.media({
      title: 'Görsel seç',
      button: { text: 'Bu görseli kullan' },
      multiple: false,
      library: { type: 'image' }
    });
    frame.on('select', function () {
      const item = frame.state().get('selection').first().toJSON();
      $input.val(item.id);
      $preview.html(previewItem(item));
    });
    frame.open();
  });

  $(document).on('click', '.ae-clear-media', function (event) {
    event.preventDefault();
    const $field = $(this).closest('.ae-field-control');
    $field.find('.ae-media-id').val('');
    $field.find('.ae-media-preview').html('<span class="ae-empty-preview">Görsel seçilmedi</span>');
  });

  $(document).on('click', '.ae-select-gallery', function (event) {
    event.preventDefault();
    const $field = $(this).closest('.ae-field-control');
    const $input = $field.find('.ae-gallery-ids');
    const $preview = $field.find('.ae-gallery-preview');
    const frame = wp.media({
      title: 'Galeri görsellerini seç',
      button: { text: 'Galeriyi kullan' },
      multiple: true,
      library: { type: 'image' }
    });
    frame.on('select', function () {
      const items = frame.state().get('selection').map(function (item) { return item.toJSON(); });
      $input.val(items.map(function (item) { return item.id; }).join(','));
      $preview.html(items.map(previewItem).join(''));
    });
    frame.open();
  });

  $(document).on('click', '.ae-clear-gallery', function (event) {
    event.preventDefault();
    const $field = $(this).closest('.ae-field-control');
    $field.find('.ae-gallery-ids').val('');
    $field.find('.ae-gallery-preview').empty();
  });

  $(document).on('click', '.ae-select-video', function (event) {
    event.preventDefault();
    const $input = $(this).closest('.ae-video-row').find('.ae-video-url');
    const frame = wp.media({
      title: 'Video seç',
      button: { text: 'Bu videoyu kullan' },
      multiple: false,
      library: { type: 'video' }
    });
    frame.on('select', function () {
      const item = frame.state().get('selection').first().toJSON();
      $input.val(item.url).trigger('change');
    });
    frame.open();
  });
})(jQuery);
