/**
 * Media Selector
 * A JavaScript utility for selecting media from the media library
 */
class MediaSelector {
    constructor(options = {}) {
        // Ensure selectedIds is always an array
        if (options.selectedIds && !Array.isArray(options.selectedIds)) {
            options.selectedIds = [options.selectedIds];
        }
        
        this.options = Object.assign({
            modalId: 'media-selector-modal',
            selectUrl: '/api/media/select',
            insertCallback: null,
            type: '', // 'image' or 'document' or empty for all
            multiple: false,
            selectedIds: [], // IDs of items to pre-select
        }, options);

        this.page = 1;
        this.search = '';
        this.items = [];
        this.pagination = {};
        this.selectedItems = [];
        
        console.log('MediaSelector initialized with options:', this.options);
        
        // Initialize the modal if not exists
        this.initModal();
        
        // Bind events
        this.bindEvents();
    }
    
    /**
     * Initialize the modal dialog
     */
    initModal() {
        if (document.getElementById(this.options.modalId)) {
            return;
        }
        
        const modalHTML = `
            <div id="${this.options.modalId}" class="fixed inset-0 overflow-y-auto hidden z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Chọn media
                                    </h3>
                                    
                                    <div class="mt-4 mb-4">
                                        <div class="flex flex-wrap items-end space-x-4">
                                            <div class="w-full sm:w-auto">
                                                <label for="media-search" class="block text-sm font-medium text-gray-700">Tìm kiếm</label>
                                                <div class="mt-1 relative rounded-md shadow-sm">
                                                    <input type="text" name="media-search" id="media-search" 
                                                        class="block w-full pr-10 sm:text-sm border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" 
                                                        placeholder="Tìm kiếm...">
                                                </div>
                                            </div>
                                            
                                            <div class="w-full sm:w-auto">
                                                <label for="media-type" class="block text-sm font-medium text-gray-700">Loại</label>
                                                <select id="media-type" name="media-type" 
                                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                                                    <option value="">Tất cả</option>
                                                    <option value="image" ${this.options.type === 'image' ? 'selected' : ''}>Hình ảnh</option>
                                                    <option value="document" ${this.options.type === 'document' ? 'selected' : ''}>Tài liệu</option>
                                                </select>
                                            </div>
                                            
                                            <button type="button" id="media-search-btn"
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                Tìm kiếm
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="border rounded-md overflow-hidden">
                                        <div id="media-items-container" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 p-4 max-h-96 overflow-y-auto">
                                            <div class="col-span-full text-center py-8 text-gray-500 media-loading">
                                                <svg class="animate-spin h-8 w-8 mx-auto text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <p class="mt-2">Đang tải...</p>
                                            </div>
                                        </div>
                                        
                                        <div id="media-pagination" class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                            <div class="flex-1 flex justify-between sm:hidden">
                                                <button type="button" class="media-prev-page relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                    Trước
                                                </button>
                                                <button type="button" class="media-next-page ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                    Sau
                                                </button>
                                            </div>
                                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                                <div>
                                                    <p class="text-sm text-gray-700 media-pagination-info">
                                                        Hiển thị <span class="font-medium">0</span> đến <span class="font-medium">0</span> trong số <span class="font-medium">0</span> kết quả
                                                    </p>
                                                </div>
                                                <div>
                                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                                        <button type="button" class="media-prev-page relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                            <span class="sr-only">Trước</span>
                                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                        
                                                        <div id="media-pagination-pages">
                                                            <!-- Pagination pages will be inserted here -->
                                                        </div>
                                                        
                                                        <button type="button" class="media-next-page relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                            <span class="sr-only">Sau</span>
                                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                    </nav>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" class="media-insert w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Chèn
                            </button>
                            <button type="button" class="media-cancel mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Hủy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHTML;
        document.body.appendChild(modalContainer.firstElementChild);
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        document.addEventListener('click', (e) => {
            // Close button
            if (e.target.closest('.media-cancel')) {
                this.close();
            }
            
            // Insert button
            if (e.target.closest('.media-insert')) {
                this.insert();
            }
            
            // Previous page
            if (e.target.closest('.media-prev-page')) {
                if (this.page > 1) {
                    this.page--;
                    this.loadItems();
                }
            }
            
            // Next page
            if (e.target.closest('.media-next-page')) {
                if (this.page < this.pagination.last_page) {
                    this.page++;
                    this.loadItems();
                }
            }
            
            // Pagination number
            if (e.target.closest('.media-page-number')) {
                const page = parseInt(e.target.closest('.media-page-number').dataset.page);
                if (page !== this.page) {
                    this.page = page;
                    this.loadItems();
                }
            }
            
            // Media item - REMOVED from here to avoid double toggle
            // We'll use only the direct click handler added to each item
        });
        
        // Search button
        const searchBtn = document.getElementById('media-search-btn');
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                const searchInput = document.getElementById('media-search');
                const typeSelect = document.getElementById('media-type');
                
                if (searchInput) this.search = searchInput.value;
                if (typeSelect) this.options.type = typeSelect.value;
                
                this.page = 1;
                this.loadItems();
            });
        }
        
        // Search input on enter
        const searchInput = document.getElementById('media-search');
        if (searchInput) {
            searchInput.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') {
                    this.search = e.target.value;
                    this.page = 1;
                    this.loadItems();
                }
            });
        }
    }
    
    /**
     * Open the media selector
     */
    open() {
        const modal = document.getElementById(this.options.modalId);
        if (modal) {
            modal.classList.remove('hidden');
            
            // Initialize selectedItems from selectedIds if provided
            if (this.options.selectedIds && this.options.selectedIds.length > 0) {
                console.log('Initializing with selected IDs:', this.options.selectedIds);
                // We'll update the actual items after loading
            }
            else if (!this.options.multiple) {
                // Clear previous selection if not multiple and no pre-selected IDs
                this.selectedItems = [];
            }
            
            // Load items
            this.loadItems();
        } else {
            console.error('Modal element not found');
        }
    }
    
    /**
     * Close the media selector
     */
    close() {
        const modal = document.getElementById(this.options.modalId);
        if (modal) {
            modal.classList.add('hidden');
        }
    }
    
    /**
     * Load media items
     */
    loadItems() {
        const container = document.getElementById('media-items-container');
        if (!container) {
            console.error('Media container not found');
            return;
        }
        
        container.innerHTML = `
            <div class="col-span-full text-center py-8 text-gray-500 media-loading">
                <svg class="animate-spin h-8 w-8 mx-auto text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2">Đang tải...</p>
            </div>
        `;
        
        // Prepare query parameters for request
        const params = new URLSearchParams();
        params.append('page', this.page);
        
        if (this.search) {
            params.append('search', this.search);
        }
        
        if (this.options.type) {
            params.append('type', this.options.type);
        }
        
        const url = `${this.options.selectUrl}?${params.toString()}`;
        console.log('Fetching media from:', url);
        
        // Using axios instead of fetch for authentication support
        axios.get(url)
            .then(response => {
                const data = response.data;
                console.log('Data received:', data);
                if (data && data.media) {
                    this.items = data.media;
                    this.pagination = data.pagination;
                    
                    // Update selectedItems from selectedIds if needed
                    if (this.options.selectedIds && this.options.selectedIds.length > 0) {
                        // Find the selected items in the loaded items
                        const selectedItemsFromIds = this.items.filter(item => 
                            this.options.selectedIds.includes(item.id)
                        );
                        
                        // If not in multiple mode, keep only the first match
                        if (!this.options.multiple && selectedItemsFromIds.length > 0) {
                            this.selectedItems = [selectedItemsFromIds[0]];
                        } else if (selectedItemsFromIds.length > 0) {
                            // In multiple mode or if we have matches
                            this.selectedItems = selectedItemsFromIds;
                        }
                        
                        console.log('Updated selectedItems from IDs:', this.selectedItems);
                    }
                    
                    this.renderItems();
                    this.renderPagination();
                } else {
                    throw new Error('Invalid data format received from server');
                }
            })
            .catch(error => {
                console.error('Error loading media items:', error);
                let errorMessage = error.message;
                
                // Add more details for specific error types
                if (error.response) {
                    // The request was made and the server responded with a status code
                    // that falls out of the range of 2xx
                    errorMessage += ` (${error.response.status}: ${error.response.statusText})`;
                    console.error('Response data:', error.response.data);
                    console.error('Response headers:', error.response.headers);
                } else if (error.request) {
                    // The request was made but no response was received
                    errorMessage += ' (No response received)';
                    console.error('Request:', error.request);
                }
                
                container.innerHTML = `
                    <div class="col-span-full text-center py-8 text-red-500">
                        <p>Đã xảy ra lỗi khi tải media: ${errorMessage}. Vui lòng thử lại.</p>
                    </div>
                `;
            });
    }
    
    /**
     * Render media items
     */
    renderItems() {
        console.log('Rendering items. Currently selected items IDs:', this.options.selectedIds);
        
        if (!Array.isArray(this.items)) {
            this.items = [];
        }
        
        // Hiển thị thông báo nếu không có data
        this.toggleEmptyMessage(this.items.length === 0);
        
        // Hiển thị items
        const itemsGrid = document.getElementById('media-items-container');
        itemsGrid.innerHTML = '';
        
        this.items.forEach(item => {
            // Kiểm tra xem item có trong danh sách đã chọn không
            const isSelected = this.options.selectedIds.includes(item.id) || 
                              this.selectedItems.some(selectedItem => selectedItem.id === item.id);
            
            console.log(`Item ${item.id} isSelected:`, isSelected);
            
            // Tạo HTML cho item
            const itemElement = document.createElement('div');
            itemElement.className = `media-item relative group/item rounded-md overflow-hidden border cursor-pointer ${isSelected ? 'ring-2 ring-green-500' : ''}`;
            itemElement.dataset.id = item.id;
            
            // Tạo hình ảnh
            let thumbnail = item.thumbnail || '/images/placeholder.png';
            itemElement.innerHTML = `
                <div class="h-32 bg-gray-100 overflow-hidden">
                    <img src="${thumbnail}" class="w-full h-full object-cover" alt="${item.name}">
                </div>
                <div class="p-2 text-sm truncate" title="${item.name}">${item.name}</div>
            `;
            
            // Thêm checkmark nếu đã chọn
            if (isSelected) {
                const checkmarkDiv = document.createElement('div');
                checkmarkDiv.className = 'absolute top-2 right-2 h-5 w-5 bg-green-500 rounded-full flex items-center justify-center';
                checkmarkDiv.innerHTML = `
                    <svg class="h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                `;
                itemElement.appendChild(checkmarkDiv);
            }
            
            // Xử lý sự kiện click
            itemElement.addEventListener('click', (e) => {
                e.stopPropagation(); // Ngăn sự kiện lan ra các thành phần cha
                console.log('Item clicked:', item.id);
                this.toggleItem(item);
            });
            
            itemsGrid.appendChild(itemElement);
        });
        
        // Cập nhật lại selectedItems từ selectedIds
        if (this.options.selectedIds && this.options.selectedIds.length > 0) {
            this.selectedItems = this.items.filter(item => this.options.selectedIds.includes(item.id));
            if (!this.options.multiple && this.selectedItems.length > 1) {
                // Nếu không phải chế độ multiple, chỉ giữ lại item đầu tiên
                this.selectedItems = [this.selectedItems[0]];
                this.options.selectedIds = [this.selectedItems[0].id];
            }
            console.log('Updated selectedItems from selectedIds:', this.selectedItems);
        }
    }
    
    /**
     * Render pagination
     */
    renderPagination() {
        const paginationInfo = document.querySelector('.media-pagination-info');
        const paginationPages = document.getElementById('media-pagination-pages');
        
        if (!paginationInfo || !paginationPages || !this.pagination) return;
        
        // Update pagination info
        if (this.pagination.total > 0) {
            paginationInfo.innerHTML = `
                Hiển thị 
                <span class="font-medium">${(this.pagination.current_page - 1) * this.pagination.per_page + 1}</span> 
                đến 
                <span class="font-medium">${Math.min(this.pagination.current_page * this.pagination.per_page, this.pagination.total)}</span> 
                trong số 
                <span class="font-medium">${this.pagination.total}</span>
                kết quả
            `;
        } else {
            paginationInfo.innerHTML = `Hiển thị <span class="font-medium">0</span> kết quả`;
        }
        
        // Update pagination pages
        paginationPages.innerHTML = '';
        
        for (let i = 1; i <= this.pagination.last_page; i++) {
            const pageButton = document.createElement('button');
            pageButton.type = 'button';
            pageButton.className = `media-page-number relative inline-flex items-center px-4 py-2 border ${i === this.page ? 'bg-green-50 border-green-500 text-green-600 z-10' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'} text-sm font-medium`;
            pageButton.dataset.page = i;
            pageButton.innerHTML = i;
            
            paginationPages.appendChild(pageButton);
        }
        
        // Update prev/next button states
        const prevButtons = document.querySelectorAll('.media-prev-page');
        const nextButtons = document.querySelectorAll('.media-next-page');
        
        prevButtons.forEach(button => {
            if (this.page === 1) {
                button.setAttribute('disabled', true);
                button.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                button.removeAttribute('disabled');
                button.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });
        
        nextButtons.forEach(button => {
            if (this.page === this.pagination.last_page || this.pagination.last_page === 0) {
                button.setAttribute('disabled', true);
                button.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                button.removeAttribute('disabled');
                button.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });
    }
    
    /**
     * Toggle selection of an item
     * @param {Object} item - Item object to toggle selection
     */
    toggleItem(item) {
        if (!item) return;
        
        const id = parseInt(item.id);
        const isSelected = this.selectedItems.some(selected => selected.id === id);
        console.log('Toggle item:', id, 'Currently selected:', isSelected);
        
        // Tìm phần tử DOM hiển thị item này
        const itemElement = document.querySelector(`.media-item[data-id="${id}"]`);
        
        if (!this.options.multiple) {
            // Chế độ chọn một item: xóa tất cả selection cũ
            this.selectedItems = [];
            this.options.selectedIds = [];
            
            // Xóa trạng thái đã chọn từ tất cả các item
            document.querySelectorAll('.media-item').forEach(el => {
                el.classList.remove('ring-2', 'ring-green-500');
                const checkmark = el.querySelector('.absolute.top-2.right-2');
                if (checkmark) checkmark.remove();
            });
            
            // Thêm item mới
            this.selectedItems.push(item);
            this.options.selectedIds.push(id);
            
            if (itemElement) {
                itemElement.classList.add('ring-2', 'ring-green-500');
                
                // Thêm checkmark
                const checkmarkDiv = document.createElement('div');
                checkmarkDiv.className = 'absolute top-2 right-2 h-5 w-5 bg-green-500 rounded-full flex items-center justify-center';
                checkmarkDiv.innerHTML = `
                    <svg class="h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                `;
                itemElement.appendChild(checkmarkDiv);
            }
        } else {
            // Chế độ chọn nhiều item: toggle selection
            if (isSelected) {
                // Nếu đã chọn thì bỏ chọn
                this.selectedItems = this.selectedItems.filter(selected => selected.id !== id);
                this.options.selectedIds = this.options.selectedIds.filter(selectedId => selectedId !== id);
                
                if (itemElement) {
                    itemElement.classList.remove('ring-2', 'ring-green-500');
                    const checkmark = itemElement.querySelector('.absolute.top-2.right-2');
                    if (checkmark) checkmark.remove();
                }
            } else {
                // Nếu chưa chọn thì chọn
                this.selectedItems.push(item);
                this.options.selectedIds.push(id);
                
                if (itemElement) {
                    itemElement.classList.add('ring-2', 'ring-green-500');
                    
                    // Thêm checkmark
                    const checkmarkDiv = document.createElement('div');
                    checkmarkDiv.className = 'absolute top-2 right-2 h-5 w-5 bg-green-500 rounded-full flex items-center justify-center';
                    checkmarkDiv.innerHTML = `
                        <svg class="h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    `;
                    itemElement.appendChild(checkmarkDiv);
                }
            }
        }
        
        // Cập nhật thông tin cho form ngầm
        this.updateSelectedInfo();
        
        console.log('After toggle - Selected items:', this.selectedItems);
        console.log('After toggle - Selected IDs:', this.options.selectedIds);
    }
    
    /**
     * Toggle empty message display
     * @param {boolean} show - Whether to show the empty message
     */
    toggleEmptyMessage(show) {
        const container = document.getElementById('media-items-container');
        if (!container) return;
        
        if (show) {
            container.innerHTML = `
                <div class="col-span-full text-center py-8 text-gray-500">
                    <svg class="h-12 w-12 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-2">Không có media nào được tìm thấy.</p>
                </div>
            `;
        }
    }
    
    /**
     * Insert selected media
     */
    insert() {
        console.log('Insert called. Selected items:', this.selectedItems);
        
        if (this.selectedItems.length === 0) {
            // Check visually selected items in DOM as fallback
            const visuallySelectedItems = document.querySelectorAll('.media-item.ring-2.ring-green-500');
            console.log('Visually selected items:', visuallySelectedItems.length);
            
            if (visuallySelectedItems.length > 0) {
                // Recover selectedItems from visual selection
                this.selectedItems = [];
                visuallySelectedItems.forEach(element => {
                    const id = parseInt(element.dataset.id);
                    const item = this.items.find(i => i.id === id);
                    if (item) {
                        this.selectedItems.push(item);
                        // Also update selectedIds
                        if (!this.options.selectedIds.includes(id)) {
                            this.options.selectedIds.push(id);
                        }
                    }
                });
                console.log('Recovered selected items:', this.selectedItems);
            } else {
                // Hình ảnh đầu tiên được chọn mặc định nếu có sẵn và chưa có lựa chọn
                if (this.items.length > 0 && !this.options.multiple) {
                    const firstItem = this.items[0];
                    this.selectedItems = [firstItem];
                    this.options.selectedIds = [firstItem.id];
                    console.log('Auto selected first item:', firstItem);
                    
                    // Cập nhật giao diện
                    const firstElement = document.querySelector(`.media-item[data-id="${firstItem.id}"]`);
                    if (firstElement) {
                        firstElement.classList.add('ring-2', 'ring-green-500');
                        if (!firstElement.querySelector('.absolute.top-2.right-2')) {
                            const checkmarkDiv = document.createElement('div');
                            checkmarkDiv.className = 'absolute top-2 right-2 h-5 w-5 bg-green-500 rounded-full flex items-center justify-center';
                            checkmarkDiv.innerHTML = `
                                <svg class="h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            `;
                            firstElement.appendChild(checkmarkDiv);
                        }
                    }
                } else {
                    alert('Vui lòng chọn ít nhất một media.');
                    return;
                }
            }
        }
        
        if (typeof this.options.insertCallback === 'function') {
            console.log('Calling insertCallback with:', this.options.multiple ? this.selectedItems : this.selectedItems[0]);
            this.options.insertCallback(this.options.multiple ? this.selectedItems : this.selectedItems[0]);
        } else {
            console.warn('No insertCallback provided');
        }
        
        this.close();
    }
}

// Make it available globally, but only if not already defined
if (!window.MediaSelector) {
    window.MediaSelector = MediaSelector;
} 