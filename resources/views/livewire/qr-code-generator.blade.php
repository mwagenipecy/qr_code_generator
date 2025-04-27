<!-- resources/views/livewire/qr-code-generator.blade.php -->
<div class="min-h-screen bg-white">
    <!-- Header -->
    <header class="bg-emerald-500 text-white p-4 shadow-md">
        <div class="container mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <rect x="7" y="7" width="3" height="3"></rect>
                    <rect x="14" y="7" width="3" height="3"></rect>
                    <rect x="7" y="14" width="3" height="3"></rect>
                    <rect x="14" y="14" width="3" height="3"></rect>
                </svg>
                <h1 class="text-xl font-bold">Cool QR Generator</h1>
            </div>
        </div>
    </header>
    
    <!-- Main content -->
    <main class="container mx-auto p-4 mt-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- QR Code Preview -->
            <div class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-emerald-700 mb-4">QR Code Preview</h2>
                <div class="p-4 bg-white rounded-lg border-4" style="border-color: {{ $borderColor }}">
                    @if($generatedQrCode)
                        <img src="data:image/png;base64,{{ $generatedQrCode }}" alt="QR Code" class="w-48 h-48">
                    @else
                        <div class="w-48 h-48 bg-gray-200 flex items-center justify-center">
                            <p class="text-gray-500">Loading QR Code...</p>
                        </div>
                    @endif
                </div>
                <div class="mt-6 flex gap-2">
                    <button wire:click="downloadQrCode('png')" class="flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white py-2 px-4 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        PNG
                    </button>
                    <button wire:click="downloadQrCode('svg')" class="flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        SVG
                    </button>
                    <button wire:click="downloadQrCode('eps')" class="flex items-center gap-2 bg-purple-500 hover:bg-purple-600 text-white py-2 px-4 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        EPS
                    </button>
                </div>
            </div>
            
            <!-- QR Settings -->
            <div class="p-6 bg-gray-50 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-emerald-700 mb-4">Customize Your QR Code</h2>
                
                <!-- Tabs -->
                <div class="flex border-b border-gray-200 mb-4">
                    <button 
                        wire:click="setActiveTab('content')" 
                        class="py-2 px-4 {{ $activeTab === 'content' ? 'border-b-2 border-emerald-500 text-emerald-600' : 'text-gray-600' }}"
                    >
                        Content
                    </button>
                    <button 
                        wire:click="setActiveTab('style')" 
                        class="py-2 px-4 {{ $activeTab === 'style' ? 'border-b-2 border-emerald-500 text-emerald-600' : 'text-gray-600' }}"
                    >
                        Style
                    </button>
                    <button 
                        wire:click="setActiveTab('branding')" 
                        class="py-2 px-4 {{ $activeTab === 'branding' ? 'border-b-2 border-emerald-500 text-emerald-600' : 'text-gray-600' }}"
                    >
                        Branding
                    </button>
                </div>
                
                <!-- Content Tab -->
                @if($activeTab === 'content')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 mb-2">QR Code Content</label>
                            <textarea 
                                wire:model.debounce.500ms="qrValue"
                                class="w-full p-2 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500"
                                rows="4"
                                placeholder="Enter URL or text for your QR code"
                            ></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2">Content Type</label>
                            <select wire:model="contentType" class="w-full p-2 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500">
                                <option>URL</option>
                                <option>Text</option>
                                <option>Email</option>
                                <option>Phone</option>
                                <option>SMS</option>
                                <option>WiFi</option>
                            </select>
                            @if($contentType === 'WiFi')
                                <div class="mt-2 text-sm text-gray-600">
                                    Format: SSID,password,encryption_type (e.g. MyWiFi,mypassword,WPA)
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
                
                <!-- Style Tab -->
                @if($activeTab === 'style')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Border Color</label>
                            <div class="flex items-center gap-2">
                                <input 
                                    type="color" 
                                    wire:model="borderColor"
                                    class="w-10 h-10 border-0"
                                >
                                <input 
                                    type="text" 
                                    wire:model="borderColor"
                                    class="flex-1 p-2 border border-gray-300 rounded-md"
                                >
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2">QR Code Style</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button 
                                    wire:click="selectQrStyle('square')"
                                    class="p-2 border {{ $qrStyle === 'square' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }} rounded-md hover:bg-gray-100 flex items-center justify-center"
                                    type="button"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 {{ $qrStyle === 'square' ? 'text-emerald-600' : 'text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <rect x="7" y="7" width="3" height="3"></rect>
                                        <rect x="14" y="7" width="3" height="3"></rect>
                                        <rect x="7" y="14" width="3" height="3"></rect>
                                        <rect x="14" y="14" width="3" height="3"></rect>
                                    </svg>
                                </button>
                                <button 
                                    wire:click="selectQrStyle('dots')"
                                    class="p-2 border {{ $qrStyle === 'dots' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }} rounded-md hover:bg-gray-100 flex items-center justify-center"
                                    type="button"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 {{ $qrStyle === 'dots' ? 'text-emerald-600' : 'text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="8" cy="8" r="2"></circle>
                                        <circle cx="16" cy="8" r="2"></circle>
                                        <circle cx="8" cy="16" r="2"></circle>
                                        <circle cx="16" cy="16" r="2"></circle>
                                    </svg>
                                </button>
                                <button 
                                    wire:click="selectQrStyle('round')"
                                    class="p-2 border {{ $qrStyle === 'round' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }} rounded-md hover:bg-gray-100 flex items-center justify-center"
                                    type="button"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 {{ $qrStyle === 'round' ? 'text-emerald-600' : 'text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="6" ry="6"></rect>
                                        <rect x="7" y="7" width="3" height="3" rx="1" ry="1"></rect>
                                        <rect x="14" y="7" width="3" height="3" rx="1" ry="1"></rect>
                                        <rect x="7" y="14" width="3" height="3" rx="1" ry="1"></rect>
                                        <rect x="14" y="14" width="3" height="3" rx="1" ry="1"></rect>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2">QR Code Size</label>
                            <input 
                                type="range" 
                                wire:model="size" 
                                min="100" 
                                max="500" 
                                step="50" 
                                class="w-full">
                            <div class="text-center text-sm text-gray-600">{{ $size }}px</div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2">Error Correction</label>
                            <select wire:model="errorCorrection" class="w-full p-2 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="L">Low (7%)</option>
                                <option value="M">Medium (15%)</option>
                                <option value="Q">Quartile (25%)</option>
                                <option value="H">High (30%)</option>
                            </select>
                            <div class="mt-1 text-xs text-gray-500">Higher error correction allows for more damage to the QR code while remaining scannable.</div>
                        </div>
                    </div>
                @endif
                
                <!-- Branding Tab -->
                @if($activeTab === 'branding')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Company Name</label>
                            <input 
                                type="text" 
                                wire:model.debounce.500ms="companyName"
                                placeholder="Your Company Name" 
                                class="w-full p-2 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500"
                            >
                            @if($companyName)
                                <div class="mt-1 text-xs text-gray-600">Your company name will appear below the QR code.</div>
                            @endif
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2">Upload Logo</label>
                            <div class="flex items-center justify-center w-full">
                                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        @if($logoTempPath)
                                            <div class="flex items-center gap-2 mb-2 text-sm text-emerald-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                                </svg>
                                                Logo uploaded successfully!
                                            </div>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="17 8 12 3 7 8"></polyline>
                                                <line x1="12" y1="3" x2="12" y2="15"></line>
                                            </svg>
                                        @endif
                                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                        <p class="text-xs text-gray-500">SVG, PNG or JPG (MAX. 2MB)</p>
                                    </div>
                                    <input wire:model="logo" type="file" class="hidden" accept="image/*">
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2">Logo Position</label>
                            <select wire:model="logoPosition" class="w-full p-2 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500">
                                <option>Center</option>
                                <option>Top Left</option>
                                <option>Top Right</option>
                                <option>Bottom Left</option>
                                <option>Bottom Right</option>
                            </select>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="mt-12 py-6 border-t border-gray-200">
        <div class="container mx-auto text-center text-gray-500">
            <p>© 2025 Cool QR Generator. All rights reserved.</p>
        </div>
    </footer>
</div>