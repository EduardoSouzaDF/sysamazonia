@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
                <img src="https://amazonia.ibict.br/wp-content/uploads/2020/02/cropped-Prancheta-1-c%C3%B3pia-4.png"
                    class="logo" alt=" Logo">
            @else
                <img src="https://amazonia.ibict.br/wp-content/uploads/2020/02/cropped-Prancheta-1-c%C3%B3pia-4.png"
                    class="logo" alt=" Logo">
            @endif
        </a>
    </td>
</tr>
