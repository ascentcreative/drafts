<button class="{{ $class }} btn-save-draft">
    @if($icon)<i class="{{ $icon }}"> </i>@endif{{ $label }}
</button>

@once
    @push('scripts')

        <script>

            $(document).on('click', 'button.btn-save-draft', function(e) {

                e.preventDefault();

                let form = $(this).parents('form');
                let old_action = $(form).attr('action');
 
                let url = new URL(old_action);
                let params = url.searchParams;

                params.append('request_draft', 1);

                form.attr('action', url.pathname + '?' + params.toString()) ; //$(this).data('draft-route'));
                form.submit();

            });

        </script>

    @endpush
@endonce