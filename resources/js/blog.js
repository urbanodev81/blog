// Botões "Copiar" da barra de compartilhar. Ficam escondidos no HTML e só
// aparecem se o navegador tiver a área de transferência: sem script, sobram os
// links das redes, que funcionam sozinhos.
(function () {
    if (!navigator.clipboard) return;
    var aviso = document.querySelector('.blog-copiado');
    document.querySelectorAll('[data-blog-copiar]').forEach(function (item) {
        item.hidden = false;
        item.querySelector('button').addEventListener('click', function () {
            navigator.clipboard.writeText(item.getAttribute('data-blog-copiar')).then(function () {
                if (aviso) aviso.textContent = 'Copiado. É só colar.';
            }, function () {
                if (aviso) aviso.textContent = 'O navegador não deixou copiar.';
            });
        });
    });
})();
