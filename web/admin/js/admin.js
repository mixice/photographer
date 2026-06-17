ready(()=>{
    const { $ } = Uigg
    // sider toggle
    var siderToggle = $('.sider-toggle')
    if (siderToggle) {
        siderToggle.addEventListener('click', function(){
            var sider = $('.sider')
            if (sider) sider.style.display = sider.style.display === 'none' ? '' : 'none'
        })
    }

    var localUrl = window.location.href,
        pageAllName = localUrl.substring(localUrl.lastIndexOf('/') + 1),
        pageName = pageAllName.substring(0, pageAllName.indexOf('.'))
    var link = $('.sider a[href="'+pageName+'.php"]')
    if (link) {
        var foldGroup = link.closest('fold-group')
        if (foldGroup) foldGroup.classList.add('active')
    }

    // editor image max-width fix
    var t = setInterval(function(){
        var d = $('iframe')
        if (d) {
            var dc = d.contentDocument
            if (dc) {
                var s = dc.createElement('style')
                s.textContent = 'img{max-width:100%;height:auto}'
                dc.head.appendChild(s)
                clearInterval(t)
            }
        }
    }, 300)
})
