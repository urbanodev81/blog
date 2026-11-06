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

// Ampliar ao clicar (v0.7.0): só a foto do texto marcada com data-ampliar="sim".
// Abre no <dialog id="blog-foto-ampliada"> que a página põe quando há foto
// marcada. Esc, clique em qualquer lugar ou o X fecham, e o foco volta para a
// foto. Sem script, a foto fica do tamanho que está.
(function () {
    var caixa = document.getElementById('blog-foto-ampliada');
    var fotos = document.querySelectorAll('.blog-texto img[data-ampliar="sim"]');
    if (!caixa || !fotos.length || typeof caixa.showModal !== 'function') return;
    var grande = caixa.querySelector('img');
    var legenda = caixa.querySelector('p');
    var origem = null;
    function abrir(foto) {
        origem = foto;
        grande.src = foto.currentSrc || foto.src;
        grande.alt = foto.alt;
        legenda.textContent = foto.alt;
        legenda.hidden = !foto.alt;
        caixa.showModal();
    }
    fotos.forEach(function (foto) {
        foto.tabIndex = 0;
        foto.setAttribute('role', 'button');
        foto.setAttribute('aria-label', 'Ampliar a foto' + (foto.alt ? ': ' + foto.alt : ''));
        foto.addEventListener('click', function () { abrir(foto); });
        foto.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrir(foto); }
        });
    });
    caixa.addEventListener('click', function () { caixa.close(); });
    caixa.addEventListener('close', function () {
        grande.removeAttribute('src');
        if (origem) origem.focus();
    });
})();
