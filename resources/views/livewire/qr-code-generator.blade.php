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
                    <img 
                    src="{{ !empty($generatedQrCode) 
                        ? 'data:image/png;base64,' . $generatedQrCode 
                        : asset('images/qr-placeholder.png') 
                    }}" 
                    alt="QR Code" 
                    class="w-48 h-48"
                />


@else
                        <div class="w-48 h-48 bg-gray-200 flex items-center justify-center">
                            <p class="text-gray-500">Loading QR Code...</p>
                        </div>
                    @endif
                </div>
                
                <!-- Display of content type and value -->
                <div class="w-full mt-4 p-3 bg-white rounded-md border border-gray-200">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-700">Type:</span>
                        <span class="text-emerald-600">{{ $contentType }}</span>
                    </div>
                    <div class="mt-1 text-sm text-gray-800 truncate">
                        {{ $qrValue }}
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-2">
                    <button wire:click="downloadQrCode('png')" class="flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white py-2 px-3 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        PNG
                    </button>
                    <button wire:click="downloadQrCode('svg')" class="flex items-center justify-center gap-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-3 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        SVG
                    </button>
                    <button wire:click="downloadQrCode('pdf')" class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white py-2 px-3 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="12" y1="18" x2="12" y2="12"></line>
                            <line x1="9" y1="15" x2="15" y2="15"></line>
                        </svg>
                        PDF
                    </button>
                    <button wire:click="downloadQrCode('eps')" class="flex items-center justify-center gap-2 bg-purple-500 hover:bg-purple-600 text-white py-2 px-3 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        EPS
                    </button>
                </div>
                
                <!-- QR Code Layout Preview -->
                <div class="mt-6 bg-white p-4 rounded-lg border border-gray-200 w-full">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Download Layout Preview</h3>
                    <div class="flex items-center justify-center p-3 bg-gray-100 rounded">
                        <div class="flex flex-col items-center space-y-2">
                            @if($logoTempPath)
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm">
                                    <img src="{{ Storage::url($logoTempPath) }}" alt="Logo" class="w-8 h-8 object-contain">
                                </div>
                            @endif
                            <div class="w-20 h-20 border-2 border-gray-300 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <rect x="7" y="7" width="3" height="3"></rect>
                                    <rect x="14" y="7" width="3" height="3"></rect>
                                    <rect x="7" y="14" width="3" height="3"></rect>
                                    <rect x="14" y="14" width="3" height="3"></rect>
                                </svg>
                            </div>
                            @if($companyName)
                                <div class="text-xs font-medium text-gray-700">{{ $companyName }}</div>
                            @endif
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 text-center">Your downloaded QR code will have the logo above, QR code in the center, and company name below.</p>
                </div>
            </div>
            
            <!-- QR Settings -->
            <div class="p-6 bg-gray-50 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-emerald-700 mb-4">Customize Your QR Code</h2>
                
                <!-- Tabs -->
                <div class="flex border-b border-gray-200 mb-4">
                    <button 
                        wire:click="setActiveTab('content')" 
                        class="py-2 px-4 {{ $activeTab === 'content' ? 'border-b-2 border-emerald-500 text-emerald-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}"
                    >
                        Content
                    </button>
                    <button 
                        wire:click="setActiveTab('style')" 
                        class="py-2 px-4 {{ $activeTab === 'style' ? 'border-b-2 border-emerald-500 text-emerald-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}"
                    >
                        Style
                    </button>
                    <button 
                        wire:click="setActiveTab('branding')" 
                        class="py-2 px-4 {{ $activeTab === 'branding' ? 'border-b-2 border-emerald-500 text-emerald-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}"
                    >
                        Branding
                    </button>
                </div>
                
                <!-- Content Tab -->
                @if($activeTab === 'content')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">QR Code Content</label>
                            <textarea 
                                wire:model.live="qrValue"
                                class="w-full p-3 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500"
                                rows="4"
                                placeholder="Enter URL or text for your QR code"
                            ></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Content Type</label>
                            <select wire:model.live="contentType" class="w-full p-3 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500">
                                <option>URL</option>
                                <option>Text</option>
                                <option>Email</option>
                                <option>Phone</option>
                                <option>SMS</option>
                                <option>WiFi</option>
                            </select>
                            
                            <!-- Content type help text -->
                            <div class="mt-2 p-3 bg-blue-50 border border-blue-100 rounded-md">
                                @if($contentType === 'URL')
                                    <p class="text-sm text-blue-700">Enter a website URL (e.g., https://example.com)</p>
                                @elseif($contentType === 'Email')
                                    <p class="text-sm text-blue-700">Enter an email address (e.g., contact@example.com)</p>
                                @elseif($contentType === 'Phone')
                                    <p class="text-sm text-blue-700">Enter a phone number with country code (e.g., +1234567890)</p>
                                @elseif($contentType === 'SMS')
                                    <p class="text-sm text-blue-700">Enter a phone number for SMS (e.g., +1234567890)</p>
                                @elseif($contentType === 'WiFi')
                                    <p class="text-sm text-blue-700">Format: SSID,password,encryption_type (e.g., MyWiFi,mypassword,WPA)</p>
                                @else
                                    <p class="text-sm text-blue-700">Enter any text you want to encode in the QR code</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- Style Tab -->
                @if($activeTab === 'style')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Border Color</label>
                            <div class="flex items-center gap-2">
                                <input 
                                    type="color" 
                                    wire:model="borderColor"
                                    class="w-12 h-12 border-0 rounded"
                                >
                                <input 
                                    type="text" 
                                    wire:model="borderColor"
                                    class="flex-1 p-3 border border-gray-300 rounded-md uppercase"
                                >
                            </div>
                            <div class="mt-1 text-xs text-gray-500">This color will be used as the background in your downloaded QR code.</div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">QR Code Style</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button 
                                    wire:click="selectQrStyle('square')"
                                    class="p-3 border-2 {{ $qrStyle === 'square' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }} rounded-lg hover:bg-gray-100 flex flex-col items-center justify-center transition-colors"
                                    type="button"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 {{ $qrStyle === 'square' ? 'text-emerald-600' : 'text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <rect x="7" y="7" width="3" height="3"></rect>
                                        <rect x="14" y="7" width="3" height="3"></rect>
                                        <rect x="7" y="14" width="3" height="3"></rect>
                                        <rect x="14" y="14" width="3" height="3"></rect>
                                    </svg>
                                    <span class="mt-1 text-sm {{ $qrStyle === 'square' ? 'text-emerald-600 font-medium' : 'text-gray-500' }}">Square</span>
                                </button>
                                <button 
                                    wire:click="selectQrStyle('dots')"
                                    class="p-3 border-2 {{ $qrStyle === 'dots' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }} rounded-lg hover:bg-gray-100 flex flex-col items-center justify-center transition-colors"
                                    type="button"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 {{ $qrStyle === 'dots' ? 'text-emerald-600' : 'text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="8" cy="8" r="2"></circle>
                                        <circle cx="16" cy="8" r="2"></circle>
                                        <circle cx="8" cy="16" r="2"></circle>
                                        <circle cx="16" cy="16" r="2"></circle>
                                    </svg>
                                    <span class="mt-1 text-sm {{ $qrStyle === 'dots' ? 'text-emerald-600 font-medium' : 'text-gray-500' }}">Dots</span>
                                </button>
                                <button 
                                    wire:click="selectQrStyle('round')"
                                    class="p-3 border-2 {{ $qrStyle === 'round' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }} rounded-lg hover:bg-gray-100 flex flex-col items-center justify-center transition-colors"
                                    type="button"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 {{ $qrStyle === 'round' ? 'text-emerald-600' : 'text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="6" ry="6"></rect>
                                        <rect x="7" y="7" width="3" height="3" rx="1" ry="1"></rect>
                                        <rect x="14" y="7" width="3" height="3" rx="1" ry="1"></rect>
                                        <rect x="7" y="14" width="3" height="3" rx="1" ry="1"></rect>
                                        <rect x="14" y="14" width="3" height="3" rx="1" ry="1"></rect>
                                    </svg>
                                    <span class="mt-1 text-sm {{ $qrStyle === 'round' ? 'text-emerald-600 font-medium' : 'text-gray-500' }}">Rounded</span>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">QR Code Size</label>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-gray-600">Small</span>
                                <input 
                                    type="range" 
                                    wire:model="size" 
                                    min="100" 
                                    max="500" 
                                    step="50" 
                                    class="w-full">
                                <span class="text-sm text-gray-600">Large</span>
                            </div>
                            <div class="text-center text-sm font-medium text-emerald-600 mt-1">{{ $size }}px</div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Error Correction</label>
                            <select wire:model="errorCorrection" class="w-full p-3 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="L">Low (7%)</option>
                                <option value="M">Medium (15%)</option>
                                <option value="Q">Quartile (25%)</option>
                                <option value="H">High (30%)</option>
                            </select>
                            <div class="mt-2 p-3 bg-yellow-50 border border-yellow-100 rounded-md">
                                <p class="text-sm text-yellow-700">Higher error correction allows your QR code to remain scannable even if partially damaged or obscured. Choose "High" if you plan to add a logo.</p>
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- Branding Tab -->
                @if($activeTab === 'branding')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Company Name</label>
                            <input 
                                type="text" 
                                wire:model.debounce.500ms="companyName"
                                placeholder="Your Company Name" 
                                class="w-full p-3 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500"
                            >
                            @if($companyName)
                                <div class="mt-1 text-xs text-gray-600">Your company name will appear below the QR code in downloads.</div>
                            @else
                                <div class="mt-1 text-xs text-gray-500">Add your company name to brand your QR codes.</div>
                            @endif
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Upload Logo</label>
                            <div class="flex items-center justify-center w-full">
                                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        @if($logoTempPath)
                                            <div class="relative mb-3">
                                                <img src="{{ Storage::url($logoTempPath) }}" alt="Logo Preview" class="h-16 w-16 object-contain">
                                                <button wire:click="$set('logoTempPath', null)" type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="flex items-center gap-2 text-sm text-emerald-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                                </svg>
                                                Logo uploaded successfully!
                                            </div>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                            <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                            <p class="text-xs text-gray-500">SVG, PNG or JPG (MAX. 2MB)</p>
                                        @endif
                                    </div>
                                    <input wire:model="logo" type="file" class="hidden" accept="image/*">
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Logo Size</label>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-gray-600">Small</span>
                                <input 
                                    type="range" 
                                    wire:model="logoSize" 
                                    min="10" 
                                    max="40" 
                                    step="5" 
                                    class="w-full"
                                    {{ !$logoTempPath ? 'disabled' : '' }}>
                                <span class="text-sm text-gray-600">Large</span>
                            </div>
                            <div class="text-center text-sm {{ $logoTempPath ? 'font-medium text-emerald-600' : 'text-gray-400' }} mt-1">{{ $logoSize }}%</div>
                            <div class="mt-1 text-xs text-gray-500">Determines the size of your logo relative to the QR code.</div>
                        </div>
                        
                        <!-- Download Layout Preview -->
                        <div class="p-3 bg-emerald-50 border border-emerald-100 rounded-md">
                            <div class="flex items-center gap-2 mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <h4 class="text-sm font-medium text-emerald-700">New Download Layout</h4>
                            </div>
                            <p class="text-sm text-emerald-700">Your downloaded QR codes now have an improved layout with the logo above, QR code in the center, and company name below for better visual appeal.</p>
                        </div>
                    </div>
                @endif
                
                <!-- Tips Section (Always visible regardless of active tab) -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <h3 class="flex items-center text-sm font-medium text-blue-700 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        Quick Tips
                    </h3>
                    <ul class="text-xs text-blue-700 space-y-1 ml-7 list-disc">
                        <li>For best results, use high contrast colors for better scanning.</li>
                        <li>If adding a logo, use "High" error correction to ensure scannability.</li>
                        <li>The PDF download option creates a print-ready document.</li>
                        <li>Test your QR code on multiple devices before distributing.</li>
                    </ul>
                </div>
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