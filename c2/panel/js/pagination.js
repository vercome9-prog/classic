const Pagination = {
    render(containerId, currentPage, totalPages, total, onPageChange) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        
        if (totalPages <= 1) return;
        
        const pageInfo = document.createElement('span');
        pageInfo.className = 'page-info';
        pageInfo.textContent = `Page ${currentPage} of ${totalPages} (${total} total)`;
        container.appendChild(pageInfo);
        
        const prevBtn = document.createElement('button');
        prevBtn.textContent = 'Previous';
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = () => {
            if (currentPage > 1) {
                onPageChange(currentPage - 1);
            }
        };
        container.appendChild(prevBtn);
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                const btn = document.createElement('button');
                btn.textContent = i;
                if (i === currentPage) {
                    btn.className = 'active';
                }
                btn.onclick = () => onPageChange(i);
                container.appendChild(btn);
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.className = 'page-info';
                container.appendChild(ellipsis);
            }
        }
        
        const nextBtn = document.createElement('button');
        nextBtn.textContent = 'Next';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.onclick = () => {
            if (currentPage < totalPages) {
                onPageChange(currentPage + 1);
            }
        };
        container.appendChild(nextBtn);
    }
};

