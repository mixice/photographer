ready(() => {
	const { $, $$ } = Uigg
	$$('.sider-toggle').forEach(el => {
		el.addEventListener('click', () => {
			const sider = $('.sider')
			sider.style.display = sider.offsetParent ? 'none' : 'block'
		})
	})
	const pageName = location.pathname.split('/').pop().split('.')[0]
	$$(`.sider a[href="${pageName}.php"]`).forEach(el => {
		const group = el.closest('fold-group')
		if (group) group.classList.add('active')
	})

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
