class StoneVisualizer {
    constructor(product) {
        this.product = product;
        this.canvas = null;
        this.ctx = null;
        this.backgroundImage = null;
        this.stoneImage = null;
        this.isDragging = false;
        this.currentPos = { x: 0, y: 0 };
        this.scale = 1;
    }

    show() {
        this.createVisualizerModal();
        this.initializeCanvas();
        this.loadImages();
        this.setupEventListeners();
    }

    createVisualizerModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'visualizer-modal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Stone Visualizer - ${this.product.name}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="visualizer-controls mb-3">
                            <button class="btn btn-outline-primary" id="rotate-left">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            <button class="btn btn-outline-primary" id="rotate-right">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <button class="btn btn-outline-primary" id="zoom-in">
                                <i class="bi bi-zoom-in"></i>
                            </button>
                            <button class="btn btn-outline-primary" id="zoom-out">
                                <i class="bi bi-zoom-out"></i>
                            </button>
                            <select class="form-select" id="background-select">
                                <option value="modern-kitchen.jpg">Modern Kitchen</option>
                                <option value="classic-bathroom.jpg">Classic Bathroom</option>
                                <option value="luxury-living.jpg">Luxury Living Room</option>
                                <option value="outdoor-patio.jpg">Outdoor Patio</option>
                                <option value="contemporary-office.jpg">Contemporary Office</option>
                                <option value="memorial-garden.jpg">Memorial Garden</option>
                                <option value="cemetery-setting.jpg">Cemetery Setting</option>
                                <option value="neutral-background.jpg">Neutral Background</option>
                            </select>
                        </div>
                        <canvas id="visualizer-canvas"></canvas>
                        <div class="visualizer-help mt-3">
                            <small class="text-muted">
                                Drag the stone to position • Use buttons to rotate and zoom • 
                                Select different environments to visualize your stone
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        this.modal = new bootstrap.Modal(modal);
        this.modal.show();

        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    }

    initializeCanvas() {
        this.canvas = document.getElementById('visualizer-canvas');
        this.ctx = this.canvas.getContext('2d');
        
        // Set canvas size
        this.canvas.width = 800;
        this.canvas.height = 600;
        
        // Initial stone position
        this.currentPos = {
            x: this.canvas.width / 2,
            y: this.canvas.height / 2
        };
    }

    loadImages() {
        // Load background image
        this.backgroundImage = new Image();
        this.backgroundImage.src = `images/visualizer/modern-kitchen.jpg`;
        this.backgroundImage.onload = () => this.draw();

        // Load stone image
        this.stoneImage = new Image();
        this.stoneImage.src = this.product.image;
        this.stoneImage.onload = () => this.draw();
    }

    setupEventListeners() {
        // Mouse events for dragging
        this.canvas.addEventListener('mousedown', (e) => this.startDrag(e));
        this.canvas.addEventListener('mousemove', (e) => this.drag(e));
        this.canvas.addEventListener('mouseup', () => this.endDrag());
        this.canvas.addEventListener('mouseleave', () => this.endDrag());

        // Touch events for mobile
        this.canvas.addEventListener('touchstart', (e) => this.startDrag(e.touches[0]));
        this.canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            this.drag(e.touches[0]);
        });
        this.canvas.addEventListener('touchend', () => this.endDrag());

        // Control buttons
        document.getElementById('rotate-left').addEventListener('click', () => this.rotate(-90));
        document.getElementById('rotate-right').addEventListener('click', () => this.rotate(90));
        document.getElementById('zoom-in').addEventListener('click', () => this.zoom(1.1));
        document.getElementById('zoom-out').addEventListener('click', () => this.zoom(0.9));

        // Background selection
        document.getElementById('background-select').addEventListener('change', (e) => {
            this.backgroundImage.src = `images/visualizer/${e.target.value}`;
        });
    }

    startDrag(e) {
        this.isDragging = true;
        const rect = this.canvas.getBoundingClientRect();
        this.dragStart = {
            x: e.clientX - rect.left - this.currentPos.x,
            y: e.clientY - rect.top - this.currentPos.y
        };
    }

    drag(e) {
        if (!this.isDragging) return;
        
        const rect = this.canvas.getBoundingClientRect();
        this.currentPos = {
            x: e.clientX - rect.left - this.dragStart.x,
            y: e.clientY - rect.top - this.dragStart.y
        };
        
        this.draw();
    }

    endDrag() {
        this.isDragging = false;
    }

    rotate(angle) {
        this.rotation = (this.rotation || 0) + angle;
        this.draw();
    }

    zoom(factor) {
        this.scale *= factor;
        this.scale = Math.max(0.5, Math.min(2, this.scale)); // Limit zoom range
        this.draw();
    }

    draw() {
        if (!this.ctx || !this.backgroundImage || !this.stoneImage) return;

        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Draw background
        this.ctx.drawImage(this.backgroundImage, 0, 0, this.canvas.width, this.canvas.height);

        // Draw stone with transformations
        this.ctx.save();
        this.ctx.translate(this.currentPos.x, this.currentPos.y);
        this.ctx.rotate((this.rotation || 0) * Math.PI / 180);
        this.ctx.scale(this.scale, this.scale);
        
        const width = this.stoneImage.width;
        const height = this.stoneImage.height;
        this.ctx.drawImage(this.stoneImage, -width/2, -height/2, width, height);
        
        this.ctx.restore();
    }
}
