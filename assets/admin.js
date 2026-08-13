(function ($) {
  'use strict';

  function previewItem(item) {
    const thumb = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url;
    return '<div class="ae-preview-item"><img class="ae-preview-image" src="' + thumb + '" alt=""><span>#' + item.id + '</span></div>';
  }

  function syncVideoRows() {
    $('.ae-video-list .ae-video-card').each(function (index) {
      const $card = $(this);
      $card.find('[name]').each(function () {
        const name = $(this).attr('name');
        if (!name || name === 'ae_featured_video_index') return;
        $(this).attr('name', name.replace(/ae_videos\[[^\]]+\]/, 'ae_videos[' + index + ']'));
      });
      $card.find('.ae-video-featured').val(index);
      $card.find('.ae-video-card-title').text('Video ' + String(index + 1).padStart(2, '0'));
    });

    $('.ae-video-featured-value').val('0');
    const $checked = $('.ae-video-featured:checked').first();
    if ($checked.length) $checked.closest('.ae-video-card').find('.ae-video-featured-value').val('1');
  }

  $(document).on('click', '.ae-select-media', function (event) {
    event.preventDefault();
    const $field = $(this).closest('.ae-field-control');
    const $input = $field.find('.ae-media-id');
    const $preview = $field.find('.ae-media-preview');
    const frame = wp.media({ title: 'Görsel seç', button: { text: 'Bu görseli kullan' }, multiple: false, library: { type: 'image' } });
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
    const frame = wp.media({ title: 'Galeri görsellerini seç', button: { text: 'Galeriyi kullan' }, multiple: true, library: { type: 'image' } });
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

  $(document).on('click', '.ae-add-video', function (event) {
    event.preventDefault();
    const $list = $('.ae-video-list');
    const index = Number($list.attr('data-next-index') || $list.children().length || 0);
    const template = $('#ae-video-row-template').html().replaceAll('__INDEX__', String(index));
    $list.append(template);
    $list.attr('data-next-index', index + 1);
    if ($list.find('.ae-video-featured:checked').length === 0) $list.find('.ae-video-featured').last().prop('checked', true);
    syncVideoRows();
  });

  $(document).on('click', '.ae-remove-video', function (event) {
    event.preventDefault();
    const $card = $(this).closest('.ae-video-card');
    const wasFeatured = $card.find('.ae-video-featured').is(':checked');
    $card.remove();
    if (wasFeatured && $('.ae-video-card').length) $('.ae-video-featured').first().prop('checked', true);
    syncVideoRows();
  });

  $(document).on('click', '.ae-move-video-up', function (event) {
    event.preventDefault();
    const $card = $(this).closest('.ae-video-card');
    const $previous = $card.prev('.ae-video-card');
    if ($previous.length) $card.insertBefore($previous);
    syncVideoRows();
  });

  $(document).on('click', '.ae-move-video-down', function (event) {
    event.preventDefault();
    const $card = $(this).closest('.ae-video-card');
    const $next = $card.next('.ae-video-card');
    if ($next.length) $card.insertAfter($next);
    syncVideoRows();
  });

  $(document).on('change', '.ae-video-featured', syncVideoRows);

  $(document).on('click', '.ae-select-video', function (event) {
    event.preventDefault();
    const $card = $(this).closest('.ae-video-card');
    const frame = wp.media({ title: 'Video seç', button: { text: 'Bu videoyu kullan' }, multiple: false, library: { type: 'video' } });
    frame.on('select', function () {
      const item = frame.state().get('selection').first().toJSON();
      $card.find('.ae-video-url').val(item.url);
      $card.find('.ae-video-attachment-id').val(item.id || '');
    });
    frame.open();
  });

  $(document).on('input change', '.ae-video-url', function () {
    const $card = $(this).closest('.ae-video-card');
    if (!String($(this).val() || '').includes('/uploads/')) $card.find('.ae-video-attachment-id').val('');
  });

  $(document).on('click', '.ae-select-video-poster', function (event) {
    event.preventDefault();
    const $card = $(this).closest('.ae-video-card');
    const frame = wp.media({ title: 'Video posteri seç', button: { text: 'Bu görseli kullan' }, multiple: false, library: { type: 'image' } });
    frame.on('select', function () {
      const item = frame.state().get('selection').first().toJSON();
      $card.find('.ae-video-poster-id').val(item.id);
      $card.find('.ae-video-poster-preview').html(previewItem(item));
    });
    frame.open();
  });

  $(document).on('click', '.ae-clear-video-poster', function (event) {
    event.preventDefault();
    const $card = $(this).closest('.ae-video-card');
    $card.find('.ae-video-poster-id').val('');
    $card.find('.ae-video-poster-preview').html('<span class="ae-empty-preview">Görsel seçilmedi</span>');
  });

  $(function () { syncVideoRows(); });
})(jQuery);
