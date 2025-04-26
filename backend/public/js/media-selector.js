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
            isFeaturedImage: false, // Whether this is for selecting a featured image
        }, options);

        this.page = 1;
        this.search = '';
        this.items = [];
        this.pagination = {};
        this.selectedItems = [];
        
        console.log('MediaSelector initialized with options:', this.options);
        
        // Kiểm tra tính tương thích của trình duyệt
        this.checkBrowserCompatibility();
        
        // Initialize the modal if not exists
        this.initModal();
        
        // Bind events
        this.bindEvents();
    }
    
    /**
     * Kiểm tra tính tương thích của trình duyệt với các tính năng cần thiết
     * và ghi log thông tin
     */
    checkBrowserCompatibility() {
        // Kiểm tra tính tương thích của setRangeText
        const textarea = document.createElement('textarea');
        const hasSetRangeText = typeof textarea.setRangeText === 'function';
        console.log('Browser supports setRangeText:', hasSetRangeText);
        
        // Kiểm tra tính tương thích của Selection API
        const hasSelectionAPI = typeof window.getSelection === 'function';
        console.log('Browser supports Selection API:', hasSelectionAPI);
        
        // Kiểm tra tính tương thích của Range API
        const hasRangeAPI = typeof document.createRange === 'function';
        console.log('Browser supports Range API:', hasRangeAPI);
        
        // Lưu thông tin để sử dụng sau này
        this.compatibility = {
            hasSetRangeText,
            hasSelectionAPI,
            hasRangeAPI
        };
        
        return this.compatibility;
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
        
        // Lưu trữ items để sử dụng sau này
        this._itemsCache = this.items;
        
        // Hiển thị thông báo nếu không có data
        this.toggleEmptyMessage(this.items.length === 0);
        
        // Hiển thị items
        const itemsGrid = document.getElementById('media-items-container');
        itemsGrid.innerHTML = '';
        
        this.items.forEach(item => {
            // Kiểm tra xem item có trong danh sách đã chọn không
            const isSelected = Array.isArray(this.options.selectedIds) && 
                              this.options.selectedIds.includes(parseInt(item.id)) || 
                              this.selectedItems.some(selectedItem => parseInt(selectedItem.id) === parseInt(item.id));
            
            console.log(`Item ${item.id} isSelected:`, isSelected);
            
            // Tạo HTML cho item
            const itemElement = document.createElement('div');
            itemElement.className = `media-item relative group/item rounded-md overflow-hidden border cursor-pointer ${isSelected ? 'ring-2 ring-green-500' : ''}`;
            itemElement.dataset.id = item.id;
            
            // Tạo hình ảnh
            let thumbnail = item.thumbnail || '/storage/images/placeholder.png';
            if (item.url) {
                thumbnail = item.url; // Sử dụng URL trực tiếp nếu có
            }
            
            itemElement.innerHTML = `
                <div class="h-32 bg-gray-100 overflow-hidden">
                    <img src="${thumbnail}" class="w-full h-full object-cover" alt="${item.name || 'Media item'}" onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22100%22%20height%3D%22100%22%20viewBox%3D%220%200%20100%20100%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20fill%3D%22%23f8f9fa%22%20width%3D%22100%22%20height%3D%22100%22%2F%3E%3Ctext%20fill%3D%22%23999%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2210%22%20text-anchor%3D%22middle%22%20x%3D%2250%22%20y%3D%2255%22%3E${item.name || 'Media'}%3C%2Ftext%3E%3C%2Fsvg%3E'">
                </div>
                <div class="p-2 text-sm truncate" title="${item.name || 'Media item'}">${item.name || 'Media item'}</div>
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
        if (Array.isArray(this.options.selectedIds) && this.options.selectedIds.length > 0) {
            // Đảm bảo chỉ lấy những item thực sự tồn tại trong danh sách items hiện tại
            const foundItems = this.items.filter(item => 
                this.options.selectedIds.includes(parseInt(item.id))
            );
            
            // Nếu có items phù hợp, cập nhật selectedItems
            if (foundItems.length > 0) {
                this.selectedItems = foundItems;
                
                // Nếu không phải chế độ multiple, chỉ giữ lại item đầu tiên
                if (!this.options.multiple && this.selectedItems.length > 1) {
                    this.selectedItems = [this.selectedItems[0]];
                    this.options.selectedIds = [parseInt(this.selectedItems[0].id)];
                }
                
                console.log('Updated selectedItems from selectedIds:', this.selectedItems);
            } else {
                // Nếu không tìm thấy item nào trong trang hiện tại, có thể item đã chọn nằm ở trang khác
                console.log('Selected items not found in current page. IDs:', this.options.selectedIds);
            }
        }
        
        // Hiển thị thông tin về item đã chọn (nếu có)
        if (typeof this.updateSelectedInfo === 'function') {
            this.updateSelectedInfo();
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
        
        // Thêm phương thức updateSelectedInfo nếu chưa được định nghĩa
        if (typeof this.updateSelectedInfo !== 'function') {
            // Không làm gì cả nếu phương thức không tồn tại
            console.log('updateSelectedInfo is not defined, skipping.');
        } else {
            this.updateSelectedInfo();
        }
        
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
            // Đảm bảo các đối tượng media được chọn đầy đủ các trường cần thiết
            const mediaToInsert = this.options.multiple ? this.selectedItems : this.selectedItems[0];
            
            // Kiểm tra và dự phòng nếu item không có id
            if (this.options.multiple) {
                // Xử lý các item trong mảng
                const safeItems = mediaToInsert.map(item => {
                    // Đảm bảo item có id, url, và name
                    const safeItem = {
                        id: item && item.id ? item.id : 0,
                        url: '',
                        name: item && item.name ? item.name : 'Unknown',
                        file_path: item && item.file_path ? item.file_path : ''
                    };
                    
                    // Đảm bảo url được tạo đúng cách
                    if (item && item.url) {
                        safeItem.url = item.url;
                    } else if (item && item.file_path) {
                        // Nếu không có URL nhưng có file_path, tạo URL từ file_path
                        safeItem.url = '/storage/' + item.file_path;
                    } else if (item && item.thumbnail) {
                        safeItem.url = item.thumbnail;
                    } else {
                        safeItem.url = '/storage/images/placeholder.png';
                    }
                    
                    return safeItem;
                });
                console.log('Calling insertCallback with safe items:', safeItems);
                this.options.insertCallback(safeItems);
            } else {
                // Xử lý item đơn lẻ
                const safeItem = {
                    id: mediaToInsert && mediaToInsert.id ? mediaToInsert.id : 0,
                    url: '',
                    name: mediaToInsert && mediaToInsert.name ? mediaToInsert.name : 'Unknown',
                    file_path: mediaToInsert && mediaToInsert.file_path ? mediaToInsert.file_path : ''
                };
                
                // Đảm bảo url được tạo đúng cách
                if (mediaToInsert && mediaToInsert.url) {
                    safeItem.url = mediaToInsert.url;
                } else if (mediaToInsert && mediaToInsert.file_path) {
                    // Nếu không có URL nhưng có file_path, tạo URL từ file_path
                    safeItem.url = '/storage/' + mediaToInsert.file_path;
                } else if (mediaToInsert && mediaToInsert.thumbnail) {
                    safeItem.url = mediaToInsert.thumbnail;
                } else {
                    safeItem.url = '/storage/images/placeholder.png';
                }
                
                console.log('Calling insertCallback with safe item:', safeItem);
                
                // Đảm bảo rằng callback được gọi với trường thông tin đầy đủ và chính xác
                try {
                    this.options.insertCallback(safeItem);
                } catch (e) {
                    console.error('Error in insertCallback:', e);
                    // Fallback nếu callback gặp lỗi
                    alert('Đã xảy ra lỗi khi chèn media. Vui lòng thử lại.');
                }
            }
        } else {
            console.warn('No insertCallback provided');
        }
        
        this.close();
    }

    /**
     * Insert selected media items
     */
    insertMedia() {
        // Check if we have something selected
        if (this.selectedItems.length === 0) {
            this.showMessage('Vui lòng chọn ít nhất một mục', 'error');
            return;
        }
        
        // Get the first selected item
        const mediaItem = this.selectedItems[0];
        
        console.log('Inserting media item:', mediaItem);
        
        // If a callback function is provided, call it with the selected item
        if (typeof this.options.insertCallback === 'function') {
            try {
                this.options.insertCallback(mediaItem);
                console.log('Insert callback executed successfully');
                
                // Ensure media IDs are stored for later reference
                // Only store in content_media_ids if NOT a featured image
                if (mediaItem.id && !this.options.isFeaturedImage) {
                    // Try to find and update the hidden input for media IDs
                    const mediaIdsInput = document.getElementById('content_media_ids');
                    if (mediaIdsInput) {
                        let currentIds = mediaIdsInput.value.split(',').filter(id => id.trim() !== '');
                        if (!currentIds.includes(mediaItem.id.toString())) {
                            currentIds.push(mediaItem.id.toString());
                            mediaIdsInput.value = currentIds.join(',');
                            console.log('Updated content_media_ids input with:', mediaIdsInput.value);
                        }
                    }
                }
                
                // Close the modal
                this.close();
                
                // If this is a featured image selection, try to send an AJAX update
                if (mediaItem.id && this.options.isFeaturedImage) {
                    this.updateFeaturedImage(mediaItem);
                }
            } catch (error) {
                console.error('Error in insert callback:', error);
                this.showMessage('Có lỗi xảy ra khi chèn media: ' + error.message, 'error');
            }
        } else {
            console.warn('No insert callback provided');
            this.showMessage('Không có xử lý cho việc chèn media này', 'error');
        }
    }
    
    /**
     * Update featured image via AJAX
     * 
     * @param {Object} mediaItem - The selected media item
     */
    updateFeaturedImage(mediaItem) {
        // Get the article ID from the URL
        const urlParts = window.location.pathname.split('/');
        let articleId = null;
        
        // Find the article ID in the URL
        for (let i = 0; i < urlParts.length; i++) {
            if (urlParts[i] === 'approved-articles' && i < urlParts.length - 1) {
                // Check if the next part is edit or a number
                if (urlParts[i+1] !== 'create' && urlParts[i+1] !== 'edit') {
                    articleId = urlParts[i+1];
                    break;
                } else if (urlParts[i+1] === 'edit' && i >= 1) {
                    // The article ID should be before 'edit'
                    articleId = urlParts[i-1];
                    break;
                }
            }
        }
        
        if (!articleId) {
            console.warn('Could not determine article ID from URL');
            return;
        }
        
        // Prepare and send the AJAX request
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Create the form data
        const formData = new FormData();
        formData.append('featured_image_id', mediaItem.id);
        
        // Send the request
        fetch(`/admin/approved-articles/${articleId}/update-featured-image`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Featured image updated successfully', data);
                
                // Show a success message
                const successMessage = document.createElement('div');
                successMessage.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50 fade-out';
                successMessage.innerText = 'Ảnh đại diện đã được cập nhật';
                document.body.appendChild(successMessage);
                
                // Remove the message after 3 seconds
                setTimeout(() => {
                    successMessage.remove();
                }, 3000);
            } else {
                console.error('Error updating featured image:', data.message);
            }
        })
        .catch(error => {
            console.error('AJAX error updating featured image:', error);
        });
    }
}

// Make it available globally, but only if not already defined
if (!window.MediaSelector) {
    window.MediaSelector = MediaSelector;
} 