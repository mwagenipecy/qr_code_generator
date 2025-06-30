<!-- resources/views/livewire/qr-code-generator.blade.php -->
<div class="min-h-screen bg-white" wire:loading.class="opacity-75">
    <!-- Loading Overlay -->
    <div wire:loading class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg p-6 shadow-xl flex items-center space-x-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
            <span class="text-gray-700 font-medium">Generating QR Code...</span>
        </div>
    </div>

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
                
                <!-- QR Code Display with Loading State -->
                <div class="relative p-4 bg-white rounded-lg border-4 transition-all duration-300" style="border-color: {{ $borderColor }}">
                    <!-- Loading State for QR Code -->
                    <div wire:loading.flex wire:target="qrValue,contentType,qrStyle,errorCorrection,size,borderColor,companyName,logoPosition,logoSize,logo" 
                         class="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center rounded-lg z-10">
                        <div class="flex flex-col items-center space-y-2">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
                            <span class="text-sm text-gray-600">Updating QR Code...</span>
                        </div>
                    </div>
                    
                    @if($generatedQrCode)
                        <img 
                            src="{{ !empty($generatedQrCode) 
                                ? 'data:image/png;base64,' . $generatedQrCode 
                                : asset('images/qr-placeholder.png') 
                            }}" 
                            alt="QR Code" 
                            class="w-48 h-48 transition-opacity duration-300"
                            wire:loading.class="opacity-50"
                        />
                    @else
                        <div class="w-48 h-48 bg-gray-200 flex items-center justify-center rounded-lg">
                            <div class="flex flex-col items-center space-y-2">
                                <div class="animate-pulse bg-gray-300 rounded-lg w-32 h-32"></div>
                                <p class="text-gray-500 text-sm">Generating QR Code...</p>
                            </div>
                        </div>
                    @endif
                </div>
                
                <!-- Display of content type and value -->
                <div class="w-full mt-4 p-3 bg-white rounded-md border border-gray-200 transition-all duration-300">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-700">Type:</span>
                        <span class="text-emerald-600 capitalize">{{ $contentType }}</span>
                    </div>
                    <div class="mt-1 text-sm text-gray-800 truncate" title="{{ $qrValue }}">
                        {{ $qrValue }}
                    </div>
                    @if(strlen($qrValue) > 0)
                        <div class="mt-1 text-xs text-gray-500">
                            {{ strlen($qrValue) }} characters
                        </div>
                    @endif
                </div>

                <!-- Download Buttons with Loading States -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-2">
                    <button wire:click="downloadQrCode('png')" 
                            wire:loading.attr="disabled"
                            wire:target="downloadQrCode('png')"
                            class="flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white py-2 px-3 rounded-md transition-colors relative">
                        <div wire:loading.remove wire:target="downloadQrCode('png')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                        </div>
                        <div wire:loading wire:target="downloadQrCode('png')" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                        <span wire:loading.remove wire:target="downloadQrCode('png')">PNG</span>
                        <span wire:loading wire:target="downloadQrCode('png')" class="text-xs">Generating...</span>
                    </button>
                    
                    <button wire:click="downloadQrCode('svg')" 
                            wire:loading.attr="disabled"
                            wire:target="downloadQrCode('svg')"
                            class="flex items-center justify-center gap-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white py-2 px-3 rounded-md transition-colors relative">
                        <div wire:loading.remove wire:target="downloadQrCode('svg')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                        </div>
                        <div wire:loading wire:target="downloadQrCode('svg')" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                        <span wire:loading.remove wire:target="downloadQrCode('svg')">SVG</span>
                        <span wire:loading wire:target="downloadQrCode('svg')" class="text-xs">Generating...</span>
                    </button>
                    
                    <button wire:click="downloadQrCode('pdf')" 
                            wire:loading.attr="disabled"
                            wire:target="downloadQrCode('pdf')"
                            class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white py-2 px-3 rounded-md transition-colors relative">
                        <div wire:loading.remove wire:target="downloadQrCode('pdf')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="12" y1="18" x2="12" y2="12"></line>
                                <line x1="9" y1="15" x2="15" y2="15"></line>
                            </svg>
                        </div>
                        <div wire:loading wire:target="downloadQrCode('pdf')" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                        <span wire:loading.remove wire:target="downloadQrCode('pdf')">PDF</span>
                        <span wire:loading wire:target="downloadQrCode('pdf')" class="text-xs">Generating...</span>
                    </button>
                    
                    <button wire:click="downloadQrCode('eps')" 
                            wire:loading.attr="disabled"
                            wire:target="downloadQrCode('eps')"
                            class="flex items-center justify-center gap-2 bg-purple-500 hover:bg-purple-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white py-2 px-3 rounded-md transition-colors relative">
                        <div wire:loading.remove wire:target="downloadQrCode('eps')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                        </div>
                        <div wire:loading wire:target="downloadQrCode('eps')" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                        <span wire:loading.remove wire:target="downloadQrCode('eps')">EPS</span>
                        <span wire:loading wire:target="downloadQrCode('eps')" class="text-xs">Generating...</span>
                    </button>
                </div>
                
                <!-- Enhanced QR Code Layout Preview -->
                <div class="mt-6 bg-white p-4 rounded-lg border border-gray-200 w-full">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Download Layout Preview</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Standard Layout Preview -->
                        <div class="p-3 bg-gray-100 rounded border {{ $downloadLayout === 'standard' ? 'ring-2 ring-emerald-500' : '' }}">
                            <h4 class="text-xs font-medium text-gray-600 mb-2">Standard Layout</h4>
                            <div class="flex flex-col items-center space-y-2">
                                @if($logoTempPath)
                                    <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <img src="{{ Storage::url($logoTempPath) }}" alt="Logo" class="w-6 h-6 object-contain">
                                    </div>
                                @endif
                                <div class="w-16 h-16 border-2 border-gray-300 flex items-center justify-center rounded" style="border-color: {{ $borderColor }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <rect x="7" y="7" width="3" height="3"></rect>
                                        <rect x="14" y="7" width="3" height="3"></rect>
                                        <rect x="7" y="14" width="3" height="3"></rect>
                                        <rect x="14" y="14" width="3" height="3"></rect>
                                    </svg>
                                </div>
                                @if($companyName)
                                    <div class="text-xs font-medium text-gray-700 text-center">{{ Str::limit($companyName, 15) }}</div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Side by Side Layout Preview -->
                        <div class="p-3 bg-gray-100 rounded border {{ $downloadLayout === 'sides' ? 'ring-2 ring-emerald-500' : '' }}">
                            <h4 class="text-xs font-medium text-gray-600 mb-2">Side Layout</h4>
                            <div class="flex items-center justify-between space-x-1">
                                @if($logoTempPath)
                                    <div class="w-6 h-6 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <img src="{{ Storage::url($logoTempPath) }}" alt="Logo" class="w-4 h-4 object-contain">
                                    </div>
                                @else
                                    <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                                @endif
                                <div class="w-12 h-12 border-2 border-gray-300 flex items-center justify-center rounded" style="border-color: {{ $borderColor }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <rect x="7" y="7" width="3" height="3"></rect>
                                        <rect x="14" y="7" width="3" height="3"></rect>
                                        <rect x="7" y="14" width="3" height="3"></rect>
                                        <rect x="14" y="14" width="3" height="3"></rect>
                                    </svg>
                                </div>
                                @if($companyName)
                                    <div class="text-xs font-medium text-gray-700 text-right w-6 truncate" title="{{ $companyName }}">{{ Str::limit($companyName, 8) }}</div>
                                @else
                                    <div class="w-6 h-6 bg-gray-300 rounded"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3 text-center">Choose your preferred download layout style above.</p>
                </div>
            </div>
            
            <!-- QR Settings -->
            <div class="p-6 bg-gray-50 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-emerald-700 mb-4">Customize Your QR Code</h2>
                
                <!-- Tabs with Loading States -->
                <div class="flex border-b border-gray-200 mb-4">
                    <button 
                        wire:click="setActiveTab('content')" 
                        wire:loading.attr="disabled"
                        wire:target="setActiveTab"
                        class="relative py-2 px-4 transition-all duration-200 {{ $activeTab === 'content' ? 'border-b-2 border-emerald-500 text-emerald-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}"
                    >
                        <span wire:loading.remove wire:target="setActiveTab('content')">Content</span>
                        <span wire:loading wire:target="setActiveTab('content')" class="flex items-center space-x-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-emerald-500"></div>
                            <span>Loading...</span>
                        </span>
                    </button>
                    <button 
                        wire:click="setActiveTab('style')" 
                        wire:loading.attr="disabled"
                        wire:target="setActiveTab"
                        class="relative py-2 px-4 transition-all duration-200 {{ $activeTab === 'style' ? 'border-b-2 border-emerald-500 text-emerald-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}"
                    >
                        <span wire:loading.remove wire:target="setActiveTab('style')">Style</span>
                        <span wire:loading wire:target="setActiveTab('style')" class="flex items-center space-x-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-emerald-500"></div>
                            <span>Loading...</span>
                        </span>
                    </button>
                    <button 
                        wire:click="setActiveTab('branding')" 
                        wire:loading.attr="disabled"
                        wire:target="setActiveTab"
                        class="relative py-2 px-4 transition-all duration-200 {{ $activeTab === 'branding' ? 'border-b-2 border-emerald-500 text-emerald-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}"
                    >
                        <span wire:loading.remove wire:target="setActiveTab('branding')">Branding</span>
                        <span wire:loading wire:target="setActiveTab('branding')" class="flex items-center space-x-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-emerald-500"></div>
                            <span>Loading...</span>
                        </span>
                    </button>
                    <button 
                        wire:click="setActiveTab('layout')" 
                        wire:loading.attr="disabled"
                        wire:target="setActiveTab"
                        class="relative py-2 px-4 transition-all duration-200 {{ $activeTab === 'layout' ? 'border-b-2 border-emerald-500 text-emerald-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}"
                    >
                        <span wire:loading.remove wire:target="setActiveTab('layout')">Layout</span>
                        <span wire:loading wire:target="setActiveTab('layout')" class="flex items-center space-x-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-emerald-500"></div>
                            <span>Loading...</span>
                        </span>
                    </button>
                </div>
                
                <!-- Content Tab -->
                @if($activeTab === 'content')
                    <div class="space-y-4 animate-fadeIn">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">QR Code Content</label>
                            <div class="relative">
                                <textarea 
                                    wire:model.lazy="qrValue"
                                    class="w-full p-3 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                    rows="4"
                                    placeholder="Enter URL or text for your QR code"
                                    wire:loading.attr="disabled"
                                    wire:target="qrValue"
                                ></textarea>
                                <div wire:loading wire:target="qrValue" class="absolute top-2 right-2">
                                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-emerald-500"></div>
                                </div>
                            </div>
                            @if(strlen($qrValue) > 0)
                                <div class="mt-1 text-xs text-gray-500">
                                    Characters: {{ strlen($qrValue) }}/2000
                                    @if(strlen($qrValue) > 1500)
                                        <span class="text-orange-500">⚠️ Very long content may be harder to scan</span>
                                    @elseif(strlen($qrValue) > 1000)
                                        <span class="text-yellow-500">⚠️ Long content</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Content Type</label>
                            <select wire:model.lazy="contentType" 
                                    class="w-full p-3 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                    wire:loading.attr="disabled"
                                    wire:target="contentType">
                                <option value="URL">URL</option>
                                <option value="Text">Text</option>
                                <option value="Email">Email</option>
                                <option value="Phone">Phone</option>
                                <option value="SMS">SMS</option>
                                <option value="WiFi">WiFi</option>
                                <option value="VCard">Contact Card</option>
                            </select>
                            
                            <!-- Enhanced Content type help text -->
                            <div class="mt-2 p-3 bg-blue-50 border border-blue-100 rounded-md transition-all duration-300">
                                @if($contentType === 'URL')
                                    <p class="text-sm text-blue-700">💡 Enter a website URL (e.g., https://example.com)</p>
                                    <p class="text-xs text-blue-600 mt-1">Tip: Include https:// for better compatibility</p>
                                @elseif($contentType === 'Email')
                                    <p class="text-sm text-blue-700">📧 Enter an email address (e.g., contact@example.com)</p>
                                    <p class="text-xs text-blue-600 mt-1">Tip: This will open the default email app</p>
                                @elseif($contentType === 'Phone')
                                    <p class="text-sm text-blue-700">📞 Enter a phone number with country code (e.g., +1234567890)</p>
                                    <p class="text-xs text-blue-600 mt-1">Tip: Include + and country code for international compatibility</p>
                                @elseif($contentType === 'SMS')
                                    <p class="text-sm text-blue-700">💬 Enter a phone number for SMS (e.g., +1234567890)</p>
                                    <p class="text-xs text-blue-600 mt-1">Tip: This will open the SMS app with the number pre-filled</p>
                                @elseif($contentType === 'WiFi')
                                    <p class="text-sm text-blue-700">📶 Format: SSID,password,encryption_type (e.g., MyWiFi,mypassword,WPA)</p>
                                    <p class="text-xs text-blue-600 mt-1">Tip: Supported encryption: WPA, WEP, or leave empty for open networks</p>
                                @elseif($contentType === 'VCard')
                                    <p class="text-sm text-blue-700">👤 Format: Name,Phone,Email,Company (e.g., John Doe,+1234567890,john@example.com,Company Inc)</p>
                                    <p class="text-xs text-blue-600 mt-1">Tip: This creates a contact card that can be saved to phone</p>
                                @else
                                    <p class="text-sm text-blue-700">📝 Enter any text you want to encode in the QR code</p>
                                    <p class="text-xs text-blue-600 mt-1">Tip: Shorter text creates simpler, more scannable QR codes</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- Style Tab -->
                @if($activeTab === 'style')
                    <div class="space-y-4 animate-fadeIn">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Border Color</label>
                            <div class="flex items-center gap-2">
                                <input 
                                    type="color" 
                                    wire:model="borderColor"
                                    class="w-12 h-12 border-0 rounded cursor-pointer transition-transform hover:scale-105"
                                    wire:loading.attr="disabled"
                                    wire:target="borderColor"
                                >
                                <input 
                                    type="text" 
                                    wire:model="borderColor"
                                    class="flex-1 p-3 border border-gray-300 rounded-md uppercase font-mono text-sm transition-colors focus:ring-emerald-500 focus:border-emerald-500"
                                    placeholder="#000000"
                                    pattern="^#[0-9A-Fa-f]{6}$"
                                    wire:loading.attr="disabled"
                                    wire:target="borderColor"
                                >
                                <div wire:loading wire:target="borderColor" class="animate-spin rounded-full h-5 w-5 border-b-2 border-emerald-500"></div>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">This color will be used as the background in your downloaded QR code.</div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">QR Code Style</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button 
                                    wire:click="selectQrStyle('square')"
                                    wire:loading.attr="disabled"
                                    wire:target="selectQrStyle"
                                    class="p-3 border-2 {{ $qrStyle === 'square' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }} rounded-lg hover:bg-gray-100 flex flex-col items-center justify-center transition-all duration-200 transform hover:scale-105 disabled:opacity-50"
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
                                    wire:loading.attr="disabled"
                                    wire:target="selectQrStyle"
                                    class="p-3 border-2 {{ $qrStyle === 'dots' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }} rounded-lg hover:bg-gray-100 flex flex-col items-center justify-center transition-all duration-200 transform hover:scale-105 disabled:opacity-50"
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
                                    wire:loading.attr="disabled"
                                    wire:target="selectQrStyle"
                                    class="p-3 border-2 {{ $qrStyle === 'round' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }} rounded-lg hover:bg-gray-100 flex flex-col items-center justify-center transition-all duration-200 transform hover:scale-105 disabled:opacity-50"
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
                                <div class="flex-1 relative">
                                    <input 
                                        type="range" 
                                        wire:model="size" 
                                        min="100" 
                                        max="500" 
                                        step="50" 
                                        class="w-full appearance-none h-2 bg-gray-200 rounded-lg outline-none transition-all duration-200"
                                        wire:loading.attr="disabled"
                                        wire:target="size"
                                    >
                                    <div wire:loading wire:target="size" class="absolute top-0 right-0 -mt-1">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-emerald-500"></div>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-600">Large</span>
                            </div>
                            <div class="text-center text-sm font-medium text-emerald-600 mt-1">{{ $size }}px</div>
                            <div class="mt-1 text-xs text-gray-500">Larger sizes provide better scanning at distance but create bigger files.</div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Error Correction Level</label>
                            <select wire:model="errorCorrection" 
                                    class="w-full p-3 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                    wire:loading.attr="disabled"
                                    wire:target="errorCorrection">
                                <option value="L">Low (7%) - Smallest QR code</option>
                                <option value="M">Medium (15%) - Good balance</option>
                                <option value="Q">Quartile (25%) - Better resilience</option>
                                <option value="H">High (30%) - Best for logos</option>
                            </select>
                            <div class="mt-2 p-3 bg-yellow-50 border border-yellow-100 rounded-md">
                                <p class="text-sm text-yellow-700">
                                    <span class="font-medium">💡 Tip:</span> Higher error correction allows your QR code to remain scannable even if partially damaged or obscured. 
                                    @if($logoTempPath)
                                        <span class="font-medium text-yellow-800">Since you have a logo, "High" is automatically used for downloads.</span>
                                    @else
                                        Choose "High" if you plan to add a logo.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- Branding Tab -->
                @if($activeTab === 'branding')
                    <div class="space-y-4 animate-fadeIn">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Company Name</label>
                            <input 
                                type="text" 
                                wire:model.debounce.500ms="companyName"
                                placeholder="Your Company Name" 
                                class="w-full p-3 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                maxlength="50"
                                wire:loading.attr="disabled"
                                wire:target="companyName"
                            >
                            @if($companyName)
                                <div class="mt-1 text-xs text-emerald-600">✓ Your company name will appear below the QR code in downloads.</div>
                            @else
                                <div class="mt-1 text-xs text-gray-500">Add your company name to brand your QR codes.</div>
                            @endif
                            <div class="mt-1 text-xs text-gray-400">{{ strlen($companyName) }}/50 characters</div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Upload Logo</label>
                            <div class="flex items-center justify-center w-full">
                                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors duration-200 relative">
                                    <!-- Loading overlay for logo upload -->
                                    <div wire:loading wire:target="logo" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center rounded-lg">
                                        <div class="flex flex-col items-center space-y-2">
                                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
                                            <span class="text-sm text-gray-600">Processing logo...</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6" wire:loading.remove wire:target="logo">
                                        @if($logoTempPath)
                                            <div class="relative mb-3">
                                                <img src="{{ Storage::url($logoTempPath) }}" alt="Logo Preview" class="h-16 w-16 object-contain rounded-lg shadow-sm">
                                                <button wire:click="$set('logoTempPath', null)" type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors transform hover:scale-110">
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
                                            <p class="text-xs text-gray-500 mt-1">Click to change logo</p>
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
                            
                            <!-- Logo format recommendations -->
                            <div class="mt-2 p-3 bg-blue-50 border border-blue-100 rounded-md">
                                <h4 class="text-sm font-medium text-blue-700 mb-1">📝 Logo Recommendations:</h4>
                                <ul class="text-xs text-blue-600 space-y-1">
                                    <li>• Use square aspect ratio for best results</li>
                                    <li>• PNG format with transparent background preferred</li>
                                    <li>• High contrast logos work better</li>
                                    <li>• Avoid very detailed logos (they may not be visible)</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Logo Size</label>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-gray-600">Small</span>
                                <div class="flex-1 relative">
                                    <input 
                                        type="range" 
                                        wire:model="logoSize" 
                                        min="10" 
                                        max="40" 
                                        step="5" 
                                        class="w-full appearance-none h-2 bg-gray-200 rounded-lg outline-none transition-all duration-200"
                                        {{ !$logoTempPath ? 'disabled' : '' }}
                                        wire:loading.attr="disabled"
                                        wire:target="logoSize"
                                    >
                                    <div wire:loading wire:target="logoSize" class="absolute top-0 right-0 -mt-1">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-emerald-500"></div>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-600">Large</span>
                            </div>
                            <div class="text-center text-sm {{ $logoTempPath ? 'font-medium text-emerald-600' : 'text-gray-400' }} mt-1">{{ $logoSize }}%</div>
                            <div class="mt-1 text-xs text-gray-500">
                                @if($logoTempPath)
                                    Determines the size of your logo relative to the QR code.
                                @else
                                    Upload a logo to adjust size settings.
                                @endif
                            </div>
                            
                            @if($logoSize > 30)
                                <div class="mt-2 p-2 bg-orange-50 border border-orange-200 rounded-md">
                                    <p class="text-xs text-orange-700">⚠️ Large logos may affect QR code scannability. Test before printing.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
                
                <!-- New Layout Tab -->
                @if($activeTab === 'layout')
                    <div class="space-y-4 animate-fadeIn">
                        <div>
                            <label class="block text-gray-700 font-medium mb-3">Download Layout Style</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Standard Layout Option -->
                                <button 
                                    wire:click="$set('downloadLayout', 'standard')"
                                    class="p-4 border-2 {{ $downloadLayout === 'standard' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }} rounded-lg hover:bg-gray-50 transition-all duration-200 text-left"
                                    type="button"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-medium {{ $downloadLayout === 'standard' ? 'text-emerald-700' : 'text-gray-700' }}">Standard Layout</h3>
                                        @if($downloadLayout === 'standard')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex flex-col items-center space-y-2 py-2">
                                        <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                                        <div class="w-12 h-12 border-2 border-gray-400 rounded"></div>
                                        <div class="w-16 h-2 bg-gray-300 rounded"></div>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-2">Logo above, QR code center, company name below</p>
                                </button>
                                
                                <!-- Side Layout Option -->
                                <button 
                                    wire:click="$set('downloadLayout', 'sides')"
                                    class="p-4 border-2 {{ $downloadLayout === 'sides' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }} rounded-lg hover:bg-gray-50 transition-all duration-200 text-left"
                                    type="button"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-medium {{ $downloadLayout === 'sides' ? 'text-emerald-700' : 'text-gray-700' }}">Side by Side</h3>
                                        @if($downloadLayout === 'sides')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between space-x-2 py-2">
                                        <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                                        <div class="w-8 h-8 border-2 border-gray-400 rounded"></div>
                                        <div class="w-6 h-2 bg-gray-300 rounded"></div>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-2">Logo left, QR code center, company name right</p>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Layout Preview -->
                        <div class="p-4 bg-white rounded-lg border border-gray-200">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Live Preview</h3>
                            <div class="flex justify-center">
                                @if($downloadLayout === 'standard')
                                    <div class="flex flex-col items-center space-y-3 p-4 bg-gray-50 rounded-lg" style="background-color: {{ $borderColor }}20;">
                                        @if($logoTempPath)
                                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm">
                                                <img src="{{ Storage::url($logoTempPath) }}" alt="Logo" class="w-10 h-10 object-contain">
                                            </div>
                                        @endif
                                        <div class="w-20 h-20 border-4 border-white rounded shadow-lg" style="border-color: {{ $borderColor }}">
                                            <div class="w-full h-full bg-white flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                    <rect x="7" y="7" width="3" height="3"></rect>
                                                    <rect x="14" y="7" width="3" height="3"></rect>
                                                    <rect x="7" y="14" width="3" height="3"></rect>
                                                    <rect x="14" y="14" width="3" height="3"></rect>
                                                </svg>
                                            </div>
                                        </div>
                                        @if($companyName)
                                            <div class="text-sm font-medium text-white bg-black bg-opacity-20 px-2 py-1 rounded">{{ Str::limit($companyName, 20) }}</div>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg" style="background-color: {{ $borderColor }}20;">
                                        @if($logoTempPath)
                                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm">
                                                <img src="{{ Storage::url($logoTempPath) }}" alt="Logo" class="w-10 h-10 object-contain">
                                            </div>
                                        @endif
                                        <div class="w-16 h-16 border-4 border-white rounded shadow-lg" style="border-color: {{ $borderColor }}">
                                            <div class="w-full h-full bg-white flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                    <rect x="7" y="7" width="3" height="3"></rect>
                                                    <rect x="14" y="7" width="3" height="3"></rect>
                                                    <rect x="7" y="14" width="3" height="3"></rect>
                                                    <rect x="14" y="14" width="3" height="3"></rect>
                                                </svg>
                                            </div>
                                        </div>
                                        @if($companyName)
                                            <div class="text-sm font-medium text-white bg-black bg-opacity-20 px-2 py-1 rounded">{{ Str::limit($companyName, 15) }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Layout Information -->
                        <div class="p-3 bg-blue-50 border border-blue-100 rounded-md">
                            <h4 class="text-sm font-medium text-blue-700 mb-1">💡 Layout Tips:</h4>
                            <ul class="text-xs text-blue-600 space-y-1">
                                <li>• <strong>Standard Layout:</strong> Best for business cards and vertical displays</li>
                                <li>• <strong>Side by Side:</strong> Great for horizontal displays and flyers</li>
                                <li>• Both layouts work with all file formats (PNG, SVG, PDF, EPS)</li>
                                <li>• Layout choice doesn't affect the QR code functionality</li>
                            </ul>
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
                        <li>Shorter content creates simpler, more reliable QR codes.</li>
                        <li>Always include https:// in URLs for better compatibility.</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="mt-12 py-6 border-t border-gray-200">
        <div class="container mx-auto text-center text-gray-500">
            <p>© 2025 Cool QR Generator. All rights reserved.</p>
            <p class="text-xs mt-1">Generate professional QR codes with custom branding and multiple layouts.</p>
        </div>
    </footer>

    <style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

/* Custom slider styling */
input[type="range"] {
    background: linear-gradient(to right, #10b981 0%, #10b981 50%, #e5e7eb 50%, #e5e7eb 100%);
}

input[type="range"]::-webkit-slider-thumb {
    appearance: none;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #10b981;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

input[type="range"]::-moz-range-thumb {
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #10b981;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Loading pulse animation for QR code */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

</div>

