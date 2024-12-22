document.addEventListener('DOMContentLoaded', function() {
    // Add modal styles
    const modalStyles = document.createElement('style');
    modalStyles.textContent = `
        .featured-products {
            padding: 2rem 0;
            background: #1a1a1a;
            min-height: 400px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .product-card {
            background: #333;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            position: relative;
        }
        
        .product-image {
            position: relative;
            width: 100%;
            height: 300px;
            background: #2a2a2a;
            overflow: hidden;
        }
        
        .product-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 15px;
        }
        
        .product-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.5);
            padding: 1rem;
            color: #fff;
        }
        
        .product-title {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .product-description {
            color: #aaa;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .image-modal-container {
            position: relative;
            width: 100%;
            max-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #000;
        }
        
        .modal-image {
            max-height: 80vh;
            object-fit: contain;
        }
        
        .modal-nav-buttons {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
        }
        
        .modal-nav {
            background: rgba(0, 0, 0, 0.5);
            border: none;
            color: white;
            padding: 1rem 0.5rem;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .modal-nav:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        
        .modal-nav i {
            font-size: 1.5rem;
        }
        
        #imageModal .modal-content {
            background: #000;
        }
        
        #imageModal .modal-header {
            position: absolute;
            right: 0;
            z-index: 1;
            background: transparent;
        }
        
        .no-products {
            text-align: center;
            padding: 3rem 1rem;
            background: #333;
            border-radius: 8px;
            margin: 2rem 0;
            color: #fff;
        }
        
        .no-products i {
            font-size: 3rem;
            color: #aaa;
            margin-bottom: 1rem;
        }
        
        .no-products h3 {
            color: #fff;
            margin-bottom: 0.5rem;
        }
        
        .no-products p {
            color: #aaa;
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
            }
            
            .product-image {
                height: 200px;
            }
        }
    `;
    document.head.appendChild(modalStyles);

    let currentPage = 1;
    let loading = false;
    let totalProducts = 0;
    let loadedProducts = 0;

    function initializeFeaturedProducts() {
        const container = document.querySelector('#featured-products');
        if (!container) {
            console.error('Featured products section not found');
            return;
        }
        
        const productsGrid = container.querySelector('.products-grid');
        if (!productsGrid) {
            console.error('Products grid not found');
            return;
        }
    }

    function createCarousel(images, product) {
        if (!images || images.length === 0) {
            return `<div class="product-image">
                <img src="/images/placeholder.jpg" alt="No image available" class="product-img">
            </div>`;
        }

        let carouselHtml = `<div class="product-image">`;
        
        if (images.length > 1) {
            carouselHtml += `
                <button class="carousel-nav carousel-prev" data-direction="-1">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="carousel-nav carousel-next" data-direction="1">
                    <i class="bi bi-chevron-right"></i>
                </button>`;
        }
        
        carouselHtml += `
            <img src="${images[0].path}" alt="${product.name}" class="product-img" data-current="0" data-total="${images.length}">
        </div>`;
        
        return carouselHtml;
    }

    function createProductCard(product) {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            ${createCarousel(product.images, product)}
            <div class="product-overlay">
                <h3 class="product-title">${product.name}</h3>
            </div>
        `;
        
        if (product.images && product.images.length > 1) {
            const img = card.querySelector('.product-img');
            const buttons = card.querySelectorAll('.carousel-nav');
            
            buttons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const direction = parseInt(button.dataset.direction);
                    const currentIndex = parseInt(img.dataset.current);
                    const totalImages = parseInt(img.dataset.total);
                    const newIndex = (currentIndex + direction + totalImages) % totalImages;
                    
                    img.src = product.images[newIndex].path;
                    img.dataset.current = newIndex;
                });
            });

            img.addEventListener('click', () => {
                openImageModal(product.images, parseInt(img.dataset.current));
            });
        }
        
        return card;
    }

    function openImageModal(images, startIndex) {
        const modal = document.getElementById('productModal');
        const modalImg = modal.querySelector('.modal-image');
        let currentIndex = startIndex;
        
        function updateModalImage() {
            modalImg.src = images[currentIndex].path;
            modalImg.alt = images[currentIndex].name || '';
        }
        
        updateModalImage();
        
        const prevBtn = modal.querySelector('.modal-prev');
        const nextBtn = modal.querySelector('.modal-next');
        
        function showNext(e) {
            e.preventDefault();
            currentIndex = (currentIndex + 1) % images.length;
            updateModalImage();
        }
        
        function showPrev(e) {
            e.preventDefault();
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            updateModalImage();
        }
        
        const newPrevBtn = prevBtn.cloneNode(true);
        const newNextBtn = nextBtn.cloneNode(true);
        prevBtn.parentNode.replaceChild(newPrevBtn, prevBtn);
        nextBtn.parentNode.replaceChild(newNextBtn, nextBtn);
        
        newPrevBtn.addEventListener('click', showPrev);
        newNextBtn.addEventListener('click', showNext);
        
        function handleKeyPress(e) {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                showPrev(e);
            }
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                showNext(e);
            }
            if (e.key === 'Escape') {
                modal.querySelector('[data-bs-dismiss="modal"]').click();
            }
        }
        
        document.addEventListener('keydown', handleKeyPress);
        
        modal.addEventListener('hidden.bs.modal', function () {
            document.removeEventListener('keydown', handleKeyPress);
        }, { once: true });
        
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }

    async function loadFeaturedProducts(page = 1, append = false) {
        if (loading) return;
        loading = true;
        
        try {
            const response = await fetch(`/crm/ajax/get_featured_products.php?page=${page}`);
            const data = await response.json();
            
            if (data.success) {
                const container = document.querySelector('.products-grid');
                const loadMoreBtn = document.querySelector('.load-more-btn');
                
                if (!append) {
                    container.innerHTML = '';
                    loadedProducts = 0;
                }
                
                totalProducts = parseInt(data.totalProducts);
                
                if (data.products && data.products.length > 0) {
                    data.products.forEach(product => {
                        container.appendChild(createProductCard(product));
                    });
                    
                    currentPage = parseInt(data.currentPage);
                    loadedProducts += data.products.length;
                    
                    console.log({
                        loadedProducts,
                        totalProducts,
                        shouldShowButton: loadedProducts < totalProducts
                    });
                    
                    if (loadMoreBtn) {
                        if (loadedProducts >= totalProducts) {
                            loadMoreBtn.style.display = 'none';
                            console.log('Hiding button - All products loaded');
                        } else {
                            loadMoreBtn.style.display = 'inline-block';
                            console.log('Showing button - More products available');
                        }
                    }
                } else {
                    if (loadMoreBtn) {
                        loadMoreBtn.style.display = 'none';
                    }
                    
                    if (!append && container.children.length === 0) {
                        container.innerHTML = '<div class="text-center text-light w-100">No products available</div>';
                    }
                }
            } else {
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Error loading products:', error);
            const loadMoreBtn = document.querySelector('.load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.style.display = 'none';
            }
        } finally {
            loading = false;
        }
    }

    initializeFeaturedProducts();
    loadFeaturedProducts(1);
    
    const loadMoreBtn = document.querySelector('.load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', (e) => {
            e.preventDefault();
            loadFeaturedProducts(currentPage + 1, true);
        });
    }
});
