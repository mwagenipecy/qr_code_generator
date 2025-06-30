<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\EpsImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Dompdf\Options;

class QrCodeGenerator extends Component
{
    use WithFileUploads;

    // Content properties
    public $qrValue = 'https://example.com';
    public $contentType = 'URL';
    
    // Style properties
    public $borderColor = '#10b981'; // Emerald green
    public $qrStyle = 'square';
    public $errorCorrection = 'M'; // Medium (15%)
    public $size = 300; // Default size
    
    // Branding properties
    public $companyName = '';
    public $logo = null;
    public $logoPosition = 'Center';
    public $logoTempPath = null;
    public $logoSize = 25; // Logo size as percentage of QR code size (default 25%)
    
    // Download layout properties
    public $downloadLayout = 'standard'; // 'standard' or 'sides'
    
    // Active tab
    public $activeTab = 'content';
    
    // Generated QR code
    public $generatedQrCode = null;
    
    // Performance optimization
    private $qrCodeCache = null;
    private $lastGeneratedHash = null;
    
    protected $rules = [
        'qrValue' => 'required|max:2000',
        'contentType' => 'required|in:URL,Text,Email,Phone,SMS,WiFi,VCard',
        'borderColor' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        'qrStyle' => 'required|in:square,dots,round',
        'errorCorrection' => 'required|in:L,M,Q,H',
        'logo' => 'nullable|image|max:2048', // 2MB Max
        'companyName' => 'nullable|string|max:50',
        'logoPosition' => 'nullable|in:Center,Top Left,Top Right,Bottom Left,Bottom Right',
        'logoSize' => 'integer|min:10|max:40', // Limited to 10-40% of QR code size
        'downloadLayout' => 'required|in:standard,sides',
    ];
    
    protected $messages = [
        'qrValue.required' => 'QR code content is required.',
        'qrValue.max' => 'Content is too long. Maximum 2000 characters allowed.',
        'borderColor.regex' => 'Please enter a valid hex color code (e.g., #10b981).',
        'logo.image' => 'Logo must be an image file.',
        'logo.max' => 'Logo file size must be less than 2MB.',
        'companyName.max' => 'Company name must be less than 50 characters.',
    ];
    
    public function mount()
    {
        $this->generateQrCode();
    }
    
    public function updated($propertyName)
    {
        // Validate the specific property that was updated
        $this->validateOnly($propertyName);
        
        // Regenerate QR code for relevant property changes
        if (in_array($propertyName, [
            'qrValue', 'contentType', 'qrStyle', 'errorCorrection', 
            'size', 'borderColor', 'companyName', 'logoPosition', 'logoSize'
        ])) {
            $this->generateQrCode();
        }
        
        // Process logo upload
        if ($propertyName === 'logo') {
            $this->processLogo();
            $this->generateQrCode();
        }
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        // Small delay to show loading state
        usleep(100000); // 0.1 second
    }
    
    public function selectQrStyle($style)
    {
        $this->qrStyle = $style;
        $this->generateQrCode();
    }
    
    /**
     * Generate a unique hash for current QR settings to enable caching
     */
    private function getSettingsHash()
    {
        $settings = [
            'content' => $this->formatContent(),
            'style' => $this->qrStyle,
            'errorCorrection' => $this->logoTempPath ? 'H' : $this->errorCorrection,
            'size' => $this->size,
            'borderColor' => $this->borderColor,
            'companyName' => $this->companyName,
            'logoPath' => $this->logoTempPath,
            'logoSize' => $this->logoSize,
            'logoPosition' => $this->logoPosition,
        ];
        
        return md5(serialize($settings));
    }
    
    /**
     * Process and store the uploaded logo with enhanced validation
     */
    public function processLogo()
    {
        if (!$this->logo) {
            $this->logoTempPath = null;
            return;
        }
        
        try {
            // Validate file type more strictly
            $allowedMimes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'];
            $fileMime = $this->logo->getMimeType();
            
            if (!in_array($fileMime, $allowedMimes)) {
                $this->addError('logo', 'Only JPEG, PNG, SVG, and WebP images are allowed.');
                return;
            }
            
            // Store the logo temporarily
            $this->logoTempPath = $this->logo->store('logos', 'public');
            
            // Process logo image for better display if Intervention Image is available
            if (class_exists('Intervention\\Image\\Facades\\Image')) {
                $logoPath = Storage::disk('public')->path($this->logoTempPath);
                
                try {
                    // Create optimized logo version
                    $logo = Image::make($logoPath);
                    
                    // Ensure maximum dimensions for performance
                    if ($logo->width() > 500 || $logo->height() > 500) {
                        $logo->resize(500, 500, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }
                    
                    // Create a square canvas for consistent positioning
                    $size = max($logo->width(), $logo->height());
                    $canvas = Image::canvas($size, $size, 'rgba(255, 255, 255, 0)');
                    
                    // Insert the logo in the center
                    $canvas->insert($logo, 'center');
                    
                    // Save the processed logo
                    $canvas->save($logoPath, 90); // 90% quality for good balance
                    
                } catch (\Exception $e) {
                    // If image processing fails, keep the original
                    \Log::warning('Logo processing failed: ' . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            $this->addError('logo', 'Failed to process logo. Please try a different image.');
            $this->logoTempPath = null;
            \Log::error('Logo upload failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a background for the logo with better visibility
     */
    private function createLogoBackground($size)
    {
        if (!class_exists('Intervention\\Image\\Facades\\Image')) {
            return null;
        }
        
        try {
            // Create white background with slight transparency
            $backgroundSize = intval($size * 0.9);
            $background = Image::canvas($backgroundSize, $backgroundSize, 'rgba(255, 255, 255, 0.9)');
            
            // Add subtle border for better definition
            if ($this->qrStyle === 'round' || $this->qrStyle === 'dots') {
                // Create circular background
                $background->circle($backgroundSize - 4, $backgroundSize / 2, $backgroundSize / 2, function ($draw) {
                    $draw->background('rgba(255, 255, 255, 0.95)');
                    $draw->border(2, 'rgba(0, 0, 0, 0.1)');
                });
            } else {
                // Create rounded rectangle background
                // Note: Intervention Image doesn't directly support rounded rectangles
                // This is a simplified implementation
                $background = Image::canvas($backgroundSize, $backgroundSize, 'rgba(255, 255, 255, 0.95)');
            }
            
            return $background;
        } catch (\Exception $e) {
            \Log::warning('Background creation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate QR code with caching and error handling
     */
    public function generateQrCode()
    {
        try {
            // Check if we need to regenerate based on settings hash
            $currentHash = $this->getSettingsHash();
            if ($this->lastGeneratedHash === $currentHash && $this->generatedQrCode) {
                return; // Use cached version
            }
            
            // Format content based on content type
            $content = $this->formatContent();
            
            if (empty($content)) {
                $this->generatedQrCode = null;
                return;
            }
            
            // Set error correction level (higher when logo is present)
            $errorCorrection = $this->logoTempPath ? 'H' : $this->errorCorrection;
            
            // Create renderer style with appropriate margin
            $margin = 1;
            $rendererStyle = new RendererStyle($this->size, $margin);
            
            try {
                // Try Imagick backend first for better quality
                if (extension_loaded('imagick')) {
                    $renderer = new ImageRenderer($rendererStyle, new ImagickImageBackEnd());
                    $writer = new Writer($renderer);
                    $qrCode = $writer->writeString($content);
                    
                    // Apply branding to PNG QR code
                    if (class_exists('Intervention\\Image\\Facades\\Image')) {
                        $qrCode = $this->applyBrandingToImage($qrCode);
                    }
                    
                    $this->generatedQrCode = base64_encode($qrCode);
                } else {
                    throw new \Exception('ImageMagick not available');
                }
            } catch (\Exception $e) {
                // Fallback to SVG
                $renderer = new ImageRenderer($rendererStyle, new SvgImageBackEnd());
                $writer = new Writer($renderer);
                $qrCode = $writer->writeString($content);
                
                // Apply styling and branding to SVG
                $svgQrCode = $this->styleSvgQrCode($qrCode);
                $svgQrCode = $this->applyBrandingToSvg($svgQrCode);
                
                $this->generatedQrCode = base64_encode($svgQrCode);
            }
            
            // Cache the result
            $this->lastGeneratedHash = $currentHash;
            
        } catch (\Exception $e) {
            \Log::error('QR Code generation failed: ' . $e->getMessage());
            $this->generatedQrCode = null;
            $this->addError('qrValue', 'Failed to generate QR code. Please check your content.');
        }
    }
    
    /**
     * Enhanced content formatting with better validation
     */
    private function formatContent()
    {
        $content = trim($this->qrValue);
        
        if (empty($content)) {
            return '';
        }
        
        switch ($this->contentType) {
            case 'Email':
                // Validate email format
                if (filter_var($content, FILTER_VALIDATE_EMAIL)) {
                    return "mailto:{$content}";
                }
                return "mailto:{$content}"; // Let QR handle invalid emails
                
            case 'Phone':
                // Clean and format phone number
                $phone = preg_replace('/[^0-9+]/', '', $content);
                if (!str_starts_with($phone, '+')) {
                    $phone = '+' . $phone;
                }
                return "tel:{$phone}";
                
            case 'SMS':
                $phone = preg_replace('/[^0-9+]/', '', $content);
                if (!str_starts_with($phone, '+')) {
                    $phone = '+' . $phone;
                }
                return "sms:{$phone}";
                
            case 'WiFi':
                // Enhanced WiFi format parsing
                $wifiParts = array_map('trim', explode(',', $content));
                if (count($wifiParts) >= 2) {
                    $ssid = $wifiParts[0];
                    $password = $wifiParts[1];
                    $encryption = count($wifiParts) > 2 ? strtoupper($wifiParts[2]) : 'WPA';
                    
                    // Validate encryption type
                    if (!in_array($encryption, ['WPA', 'WEP', 'nopass', ''])) {
                        $encryption = 'WPA';
                    }
                    
                    return "WIFI:T:{$encryption};S:{$ssid};P:{$password};;";
                }
                return $content; // Return as-is if format is incorrect
                
            case 'VCard':
                // Enhanced VCard format
                $vcardParts = array_map('trim', explode(',', $content));
                if (count($vcardParts) >= 2) {
                    $name = $vcardParts[0] ?? '';
                    $phone = $vcardParts[1] ?? '';
                    $email = $vcardParts[2] ?? '';
                    $company = $vcardParts[3] ?? '';
                    
                    $vcard = "BEGIN:VCARD\nVERSION:3.0\n";
                    if ($name) $vcard .= "FN:{$name}\n";
                    if ($phone) $vcard .= "TEL:{$phone}\n";
                    if ($email) $vcard .= "EMAIL:{$email}\n";
                    if ($company) $vcard .= "ORG:{$company}\n";
                    $vcard .= "END:VCARD";
                    
                    return $vcard;
                }
                return $content;
                
            case 'URL':
                // Enhance URL validation and formatting
                if (!preg_match('/^https?:\/\//', $content)) {
                    // Add https:// if no protocol specified
                    $content = 'https://' . $content;
                }
                return $content;
                
            default:
                return $content;
        }
    }
    
    /**
     * Apply enhanced branding to image-based QR code
     */
    private function applyBrandingToImage($qrCode)
    {
        try {
            if (!class_exists('Intervention\\Image\\Facades\\Image')) {
                return $qrCode;
            }

            $img = Image::make($qrCode);
            
            // Enhanced border with gradient effect
            $borderSize = 30;
            $width = $img->width() + ($borderSize * 2);
            $height = $img->height() + ($borderSize * 2);
            
            // Create canvas with enhanced background
            $canvas = Image::canvas($width, $height, $this->borderColor);
            
            // Add subtle gradient effect
            try {
                // Create a gradient overlay (simplified version)
                $gradient = Image::canvas($width, $height, 'rgba(255, 255, 255, 0.05)');
                $canvas->insert($gradient, 'top-left');
            } catch (\Exception $e) {
                // Skip gradient if it fails
            }
            
            // Place QR code with shadow effect
            $canvas->insert($img, 'center');
            
            // Enhanced logo placement
            if ($this->logoTempPath && Storage::disk('public')->exists($this->logoTempPath)) {
                $this->addLogoToCanvas($canvas, $img);
            }
            
            // Enhanced company name styling
            if (!empty($this->companyName)) {
                $this->addCompanyNameToCanvas($canvas, $width, $height, $borderSize);
            }
            
            return (string) $canvas->encode('png', 90);
            
        } catch (\Exception $e) {
            \Log::warning('Image branding failed: ' . $e->getMessage());
            return $qrCode;
        }
    }
    
    /**
     * Add logo to canvas with enhanced positioning
     */
    private function addLogoToCanvas($canvas, $qrImg)
    {
        try {
            $logoPath = Storage::disk('public')->path($this->logoTempPath);
            if (!file_exists($logoPath)) {
                return;
            }
            
            $logo = Image::make($logoPath);
            
            // Calculate logo size based on percentage
            $logoSize = intval($qrImg->width() * ($this->logoSize / 100));
            $logo->resize($logoSize, $logoSize, function ($constraint) {
                $constraint->aspectRatio();
            });
            
            // Add background for better visibility
            $background = $this->createLogoBackground($logoSize * 1.2);
            if ($background) {
                $bgPosition = $this->getLogoPosition($canvas->width(), $canvas->height(), $background->width(), $background->height());
                $canvas->insert($background, $bgPosition['position'], $bgPosition['x'], $bgPosition['y']);
            }
            
            // Add the logo
            $logoPosition = $this->getLogoPosition($canvas->width(), $canvas->height(), $logo->width(), $logo->height());
            $canvas->insert($logo, $logoPosition['position'], $logoPosition['x'], $logoPosition['y']);
            
        } catch (\Exception $e) {
            \Log::warning('Logo addition failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Add company name with enhanced styling
     */
    private function addCompanyNameToCanvas($canvas, $width, $height, $borderSize)
    {
        try {
            $fontSize = max(16, intval($width / 25));
            
            // Try to use a system font
            $fontPath = $this->findSystemFont();
            
            if ($fontPath) {
                $canvas->text($this->companyName, $width / 2, $height - ($borderSize / 2), function($font) use ($fontSize, $fontPath) {
                    $font->file($fontPath);
                    $font->size($fontSize);
                    $font->color('#FFFFFF');
                    $font->align('center');
                    $font->valign('bottom');
                });
            } else {
                // Fallback without custom font
                $canvas->text($this->companyName, $width / 2, $height - ($borderSize / 2), function($font) use ($fontSize) {
                    $font->size($fontSize);
                    $font->color('#FFFFFF');
                    $font->align('center');
                    $font->valign('bottom');
                });
            }
        } catch (\Exception $e) {
            \Log::warning('Company name addition failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Find available system font
     */
    private function findSystemFont()
    {
        $possibleFonts = [
            storage_path('fonts/arial.ttf'),
            public_path('fonts/arial.ttf'),
            '/System/Library/Fonts/Arial.ttf', // macOS
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', // Linux
            'C:\\Windows\\Fonts\\arial.ttf', // Windows
        ];
        
        foreach ($possibleFonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }
        
        return null;
    }
    
    /**
     * Calculate enhanced logo position coordinates
     */
    private function getLogoPosition($canvasWidth, $canvasHeight, $logoWidth, $logoHeight)
    {
        $padding = 25;
        
        switch ($this->logoPosition) {
            case 'Top Left':
                return ['position' => 'top-left', 'x' => $padding, 'y' => $padding];
            case 'Top Right':
                return ['position' => 'top-right', 'x' => $padding, 'y' => $padding];
            case 'Bottom Left':
                return ['position' => 'bottom-left', 'x' => $padding, 'y' => $padding];
            case 'Bottom Right':
                return ['position' => 'bottom-right', 'x' => $padding, 'y' => $padding];
            default: // Center
                return ['position' => 'center', 'x' => 0, 'y' => 0];
        }
    }
    
    /**
     * Apply enhanced branding to SVG QR code
     */
    private function applyBrandingToSvg($svgQrCode)
    {
        try {
            // Parse SVG dimensions
            preg_match('/<svg[^>]*width="([^"]*)"[^>]*height="([^"]*)"/', $svgQrCode, $matches);
            
            if (count($matches) < 3) {
                return $svgQrCode;
            }
            
            $width = floatval($matches[1]);
            $height = floatval($matches[2]);
            
            // Enhanced border and layout
            $borderSize = 30;
            $newWidth = $width + ($borderSize * 2);
            $newHeight = $height + ($borderSize * 2);
            
            if ($this->downloadLayout === 'sides') {
                return $this->applySideBySideLayoutSvg($svgQrCode);
            } else {
                return $this->applyStandardLayoutSvg($svgQrCode, $width, $height, $borderSize);
            }
            
        } catch (\Exception $e) {
            \Log::warning('SVG branding failed: ' . $e->getMessage());
            return $svgQrCode;
        }
    }
    
    /**
     * Apply standard layout to SVG
     */
    private function applyStandardLayoutSvg($svgQrCode, $width, $height, $borderSize)
    {
        $newWidth = $width + ($borderSize * 2);
        $newHeight = $height + ($borderSize * 2);
        
        // Create enhanced SVG structure
        $svgPrefix = '<?xml version="1.0" encoding="UTF-8"?>';
        $svgOpen = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="' . $newWidth . '" height="' . $newHeight . '" viewBox="0 0 ' . $newWidth . ' ' . $newHeight . '">';
        
        // Enhanced background with gradient
        $defs = '<defs>
            <linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:' . $this->borderColor . ';stop-opacity:1" />
                <stop offset="100%" style="stop-color:' . $this->darkenColor($this->borderColor, 10) . ';stop-opacity:1" />
            </linearGradient>
        </defs>';
        
        $background = '<rect x="0" y="0" width="' . $newWidth . '" height="' . $newHeight . '" fill="url(#bgGradient)"/>';
        
        // Extract and position QR code content
        preg_match('/<svg[^>]*>(.*)<\/svg>/s', $svgQrCode, $contentMatches);
        $svgContent = isset($contentMatches[1]) ? $contentMatches[1] : '';
        $qrGroup = '<g transform="translate(' . $borderSize . ',' . $borderSize . ')">' . $svgContent . '</g>';
        
        // Add logo and company name
        $logoSvg = $this->generateLogoSvg($newWidth, $newHeight);
        $companyText = $this->generateCompanyTextSvg($newWidth, $newHeight, $borderSize);
        
        return $svgPrefix . $svgOpen . $defs . $background . $qrGroup . $logoSvg . $companyText . '</svg>';
    }
    
    /**
     * Apply side-by-side layout to SVG (enhanced version)
     */
    private function applySideBySideLayoutSvg($svgQrCode)
    {
        // Parse SVG dimensions
        preg_match('/<svg[^>]*width="([^"]*)"[^>]*height="([^"]*)"/', $svgQrCode, $matches);
        $qrSize = floatval($matches[1]);
        
        $padding = 40;
        $sideWidth = 300;
        $totalWidth = $qrSize + ($sideWidth * 2) + ($padding * 2);
        $totalHeight = $qrSize + ($padding * 2);
        
        // Create enhanced side-by-side layout
        $svgPrefix = '<?xml version="1.0" encoding="UTF-8"?>';
        $svgOpen = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="' . $totalWidth . '" height="' . $totalHeight . '" viewBox="0 0 ' . $totalWidth . ' ' . $totalHeight . '">';
        
        // Enhanced background
        $defs = '<defs>
            <linearGradient id="sideBgGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" style="stop-color:' . $this->darkenColor($this->borderColor, 5) . ';stop-opacity:1" />
                <stop offset="50%" style="stop-color:' . $this->borderColor . ';stop-opacity:1" />
                <stop offset="100%" style="stop-color:' . $this->darkenColor($this->borderColor, 5) . ';stop-opacity:1" />
            </linearGradient>
        </defs>';
        
        $background = '<rect x="0" y="0" width="' . $totalWidth . '" height="' . $totalHeight . '" fill="url(#sideBgGradient)"/>';
        
        // Position QR code in center
        preg_match('/<svg[^>]*>(.*)<\/svg>/s', $svgQrCode, $contentMatches);
        $svgContent = isset($contentMatches[1]) ? $contentMatches[1] : '';
        $qrX = $sideWidth + $padding;
        $qrY = $padding;
        $qrGroup = '<g transform="translate(' . $qrX . ',' . $qrY . ')">' . $svgContent . '</g>';
        
        // Add side elements
        $logoSvg = $this->generateSideLogoSvg($sideWidth, $totalHeight, $padding);
        $companyText = $this->generateSideCompanyTextSvg($totalWidth, $totalHeight, $sideWidth, $padding);
        
        return $svgPrefix . $svgOpen . $defs . $background . $qrGroup . $logoSvg . $companyText . '</svg>';
    }
    
    /**
     * Generate logo SVG element
     */
    private function generateLogoSvg($width, $height)
    {
        if (!$this->logoTempPath || !Storage::disk('public')->exists($this->logoTempPath)) {
            return '';
        }
        
        try {
            $logoSize = $width * 0.15; // 15% of width
            $logoX = ($width - $logoSize) / 2;
            $logoY = $height * 0.1; // 10% from top
            
            $logoPath = Storage::disk('public')->path($this->logoTempPath);
            $logoMime = mime_content_type($logoPath);
            $logoData = base64_encode(file_get_contents($logoPath));
            
            // Enhanced background for logo
            $bgSize = $logoSize * 1.3;
            $bgX = $logoX - ($bgSize - $logoSize) / 2;
            $bgY = $logoY - ($bgSize - $logoSize) / 2;
            
            $background = '<circle cx="' . ($bgX + $bgSize/2) . '" cy="' . ($bgY + $bgSize/2) . '" r="' . ($bgSize/2) . '" fill="white" opacity="0.95" stroke="rgba(0,0,0,0.1)" stroke-width="1"/>';
            $logo = '<image x="' . $logoX . '" y="' . $logoY . '" width="' . $logoSize . '" height="' . $logoSize . '" xlink:href="data:' . $logoMime . ';base64,' . $logoData . '"/>';
            
            return $background . $logo;
        } catch (\Exception $e) {
            \Log::warning('Logo SVG generation failed: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Generate company text SVG element
     */
    private function generateCompanyTextSvg($width, $height, $borderSize)
    {
        if (empty($this->companyName)) {
            return '';
        }
        
        $fontSize = max(16, $width / 25);
        $textX = $width / 2;
        $textY = $height - ($borderSize / 3);
        
        // Enhanced text with shadow effect
        $shadow = '<text x="' . ($textX + 1) . '" y="' . ($textY + 1) . '" text-anchor="middle" font-family="Arial, sans-serif" font-size="' . $fontSize . '" fill="rgba(0,0,0,0.3)" font-weight="bold">' . htmlspecialchars($this->companyName) . '</text>';
        $text = '<text x="' . $textX . '" y="' . $textY . '" text-anchor="middle" font-family="Arial, sans-serif" font-size="' . $fontSize . '" fill="white" font-weight="bold">' . htmlspecialchars($this->companyName) . '</text>';
        
        return $shadow . $text;
    }
    
    /**
     * Generate side logo for side-by-side layout
     */
    private function generateSideLogoSvg($sideWidth, $totalHeight, $padding)
    {
        if (!$this->logoTempPath || !Storage::disk('public')->exists($this->logoTempPath)) {
            return '';
        }
        
        try {
            $logoSize = $sideWidth - ($padding * 2);
            $logoX = $padding;
            $logoY = ($totalHeight - $logoSize) / 2;
            
            $logoPath = Storage::disk('public')->path($this->logoTempPath);
            $logoMime = mime_content_type($logoPath);
            $logoData = base64_encode(file_get_contents($logoPath));
            
            return '<image x="' . $logoX . '" y="' . $logoY . '" width="' . $logoSize . '" height="' . $logoSize . '" xlink:href="data:' . $logoMime . ';base64,' . $logoData . '"/>';
        } catch (\Exception $e) {
            return '';
        }
    }
    
    /**
     * Generate side company text for side-by-side layout
     */
    private function generateSideCompanyTextSvg($totalWidth, $totalHeight, $sideWidth, $padding)
    {
        if (empty($this->companyName)) {
            return '';
        }
        
        $fontSize = $totalHeight / 15;
        $textX = $totalWidth - $sideWidth / 2;
        $textY = $totalHeight / 2;
        
        return '<text x="' . $textX . '" y="' . $textY . '" text-anchor="middle" font-family="Arial, sans-serif" font-size="' . $fontSize . '" fill="white" font-weight="bold" dominant-baseline="middle">' . htmlspecialchars($this->companyName) . '</text>';
    }
    
    /**
     * Darken a hex color by a percentage
     */
    private function darkenColor($hex, $percent)
    {
        $hex = str_replace('#', '', $hex);
        $r = max(0, hexdec(substr($hex, 0, 2)) - ($percent * 255 / 100));
        $g = max(0, hexdec(substr($hex, 2, 2)) - ($percent * 255 / 100));
        $b = max(0, hexdec(substr($hex, 4, 2)) - ($percent * 255 / 100));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Apply styling to SVG QR Code based on selected style
     */
    private function styleSvgQrCode($svgQrCode)
    {
        switch ($this->qrStyle) {
            case 'dots':
                // Replace rectangles with circles
                $svgQrCode = preg_replace('/<rect([^>]*)>/i', '<rect$1 rx="50%" ry="50%">', $svgQrCode);
                break;
                
            case 'round':
                // Add rounded corners
                $svgQrCode = preg_replace('/<rect([^>]*)>/i', '<rect$1 rx="30%" ry="30%">', $svgQrCode);
                break;
                
            // 'square' is default, no changes needed
        }
        
        return $svgQrCode;
    }
    
    /**
     * Enhanced QR code download with multiple format support
     */
    public function downloadQrCode($format = 'png')
    {
        try {
            // Validate format
            $allowedFormats = ['png', 'svg', 'pdf', 'eps'];
            if (!in_array($format, $allowedFormats)) {
                $this->addError('download', 'Invalid download format.');
                return;
            }
            
            // Format content
            $content = $this->formatContent();
            if (empty($content)) {
                $this->addError('qrValue', 'Please enter content for the QR code.');
                return;
            }
            
            // Set high error correction for downloads with logos
            $errorCorrection = $this->logoTempPath ? 'H' : $this->errorCorrection;
            
            // Use larger size for downloads (better quality)
            $downloadSize = 800;
            $rendererStyle = new RendererStyle($downloadSize, 1);
            
            switch ($format) {
                case 'svg':
                    return $this->downloadSvgQrCode($content, $rendererStyle);
                    
                case 'pdf':
                    return $this->downloadPdfQrCode($content, $rendererStyle);
                    
                case 'eps':
                    return $this->downloadEpsQrCode($content, $rendererStyle);
                    
                default: // png
                    return $this->downloadPngQrCode($content, $rendererStyle);
            }
            
        } catch (\Exception $e) {
            \Log::error('QR Code download failed: ' . $e->getMessage());
            $this->addError('download', 'Failed to generate download. Please try again.');
        }
    }
    
    /**
     * Download PNG QR code
     */
    private function downloadPngQrCode($content, $rendererStyle)
    {
        try {
            if (extension_loaded('imagick')) {
                $renderer = new ImageRenderer($rendererStyle, new ImagickImageBackEnd());
            } else {
                // Fallback to SVG conversion
                return $this->downloadSvgQrCode($content, $rendererStyle);
            }
            
            $writer = new Writer($renderer);
            $qrCode = $writer->writeString($content);
            
            // Apply layout based on selection
            if (class_exists('Intervention\\Image\\Facades\\Image')) {
                if ($this->downloadLayout === 'sides') {
                    $qrCode = $this->applySideBySideLayoutImage($qrCode);
                } else {
                    $qrCode = $this->applyStandardLayoutImage($qrCode);
                }
            }
            
            $fileName = $this->getFileName('png');
            
            return response()->streamDownload(function () use ($qrCode) {
                echo $qrCode;
            }, $fileName, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('PNG download failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Download SVG QR code
     */
    private function downloadSvgQrCode($content, $rendererStyle)
    {
        $renderer = new ImageRenderer($rendererStyle, new SvgImageBackEnd());
        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($content);
        
        // Apply styling and layout
        $qrCode = $this->styleSvgQrCode($qrCode);
        
        if ($this->downloadLayout === 'sides') {
            $qrCode = $this->applySideBySideLayoutSvg($qrCode);
        } else {
            $qrCode = $this->applyStandardLayoutSvg($qrCode, 800, 800, 30);
        }
        
        $fileName = $this->getFileName('svg');
        
        return response()->streamDownload(function () use ($qrCode) {
            echo $qrCode;
        }, $fileName, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }
    
    /**
     * Download PDF QR code - Enhanced version with fallback
     */
    private function downloadPdfQrCode($content, $rendererStyle)
    {
        try {
            // Try using DomPDF first (if available)
            if (class_exists('Dompdf\\Dompdf')) {
                return $this->generatePdfWithDompdf($content, $rendererStyle);
            }
            
            // Fallback to TCPDF if available
            if (class_exists('TCPDF')) {
                return $this->generatePdfWithTcpdf($content, $rendererStyle);
            }
            
            // Final fallback: Convert PNG to PDF-like format
            return $this->generateSimplePdf($content, $rendererStyle);
            
        } catch (\Exception $e) {
            \Log::error('PDF generation failed: ' . $e->getMessage());
            
            // Ultimate fallback: return PNG instead
            $this->addError('download', 'PDF generation failed. Downloading PNG instead.');
            return $this->downloadPngQrCode($content, $rendererStyle);
        }
    }
    
    /**
     * Generate PDF using DomPDF
     */
    private function generatePdfWithDompdf($content, $rendererStyle)
    {
        // Generate PNG first for better compatibility
        if (extension_loaded('imagick')) {
            $renderer = new ImageRenderer($rendererStyle, new ImagickImageBackEnd());
            $writer = new Writer($renderer);
            $qrCode = $writer->writeString($content);
            
            // Apply layout
            if (class_exists('Intervention\\Image\\Facades\\Image')) {
                if ($this->downloadLayout === 'sides') {
                    $qrCode = $this->applySideBySideLayoutImage($qrCode);
                } else {
                    $qrCode = $this->applyStandardLayoutImage($qrCode);
                }
            }
            
            // Convert to base64 for PDF
            $qrCodeBase64 = base64_encode($qrCode);
        } else {
            // Fallback to SVG
            $renderer = new ImageRenderer($rendererStyle, new SvgImageBackEnd());
            $writer = new Writer($renderer);
            $svgQrCode = $writer->writeString($content);
            $svgQrCode = $this->styleSvgQrCode($svgQrCode);
            
            if ($this->downloadLayout === 'sides') {
                $svgQrCode = $this->applySideBySideLayoutSvg($svgQrCode);
            } else {
                $svgQrCode = $this->applyStandardLayoutSvg($svgQrCode, 800, 800, 30);
            }
            
            $qrCodeBase64 = base64_encode($svgQrCode);
        }
        
        $html = $this->generatePdfHtml($qrCodeBase64, extension_loaded('imagick') ? 'png' : 'svg');
        
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'Arial');
        $options->set('enable_remote', false);
        
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $fileName = $this->getFileName('pdf');
        
        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }
    
    /**
     * Generate PDF using TCPDF (alternative)
     */
    private function generatePdfWithTcpdf($content, $rendererStyle)
    {
        // Generate PNG QR code
        if (extension_loaded('imagick')) {
            $renderer = new ImageRenderer($rendererStyle, new ImagickImageBackEnd());
        } else {
            throw new \Exception('PDF generation requires either DomPDF or ImageMagick');
        }
        
        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($content);
        
        // Apply layout
        if (class_exists('Intervention\\Image\\Facades\\Image')) {
            if ($this->downloadLayout === 'sides') {
                $qrCode = $this->applySideBySideLayoutImage($qrCode);
            } else {
                $qrCode = $this->applyStandardLayoutImage($qrCode);
            }
        }
        
        // Create TCPDF document
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $title = !empty($this->companyName) ? $this->companyName . ' - QR Code' : 'QR Code';
        $pdf->SetCreator('Cool QR Generator');
        $pdf->SetAuthor($this->companyName ?: 'Cool QR Generator');
        $pdf->SetTitle($title);
        $pdf->SetSubject('QR Code');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Add a page
        $pdf->AddPage();
        
        // Add title
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        
        // Add QR code image
        $tempFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        file_put_contents($tempFile, $qrCode);
        
        $pdf->Image($tempFile, 50, 40, 100, 100, 'PNG');
        
        // Add content info
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetY(150);
        $pdf->Cell(0, 10, 'Content Type: ' . $this->contentType, 0, 1, 'C');
        $pdf->Cell(0, 10, 'Content: ' . Str::limit($this->qrValue, 80), 0, 1, 'C');
        $pdf->Cell(0, 10, 'Generated: ' . now()->format('F j, Y \a\t g:i A'), 0, 1, 'C');
        
        // Clean up temp file
        unlink($tempFile);
        
        $fileName = $this->getFileName('pdf');
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->Output('', 'S');
        }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }
    
    /**
     * Generate simple PDF without external libraries
     */
    private function generateSimplePdf($content, $rendererStyle)
    {
        // Generate high-quality PNG
        if (extension_loaded('imagick')) {
            $renderer = new ImageRenderer($rendererStyle, new ImagickImageBackEnd());
        } else {
            throw new \Exception('PDF generation requires ImageMagick extension');
        }
        
        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($content);
        
        // Apply layout
        if (class_exists('Intervention\\Image\\Facades\\Image')) {
            if ($this->downloadLayout === 'sides') {
                $qrCode = $this->applySideBySideLayoutImage($qrCode);
            } else {
                $qrCode = $this->applyStandardLayoutImage($qrCode);
            }
        }
        
        // Create a basic PDF structure (simplified)
        $fileName = $this->getFileName('pdf');
        
        // For now, return PNG with PDF extension and appropriate headers
        // This is a fallback when no PDF libraries are available
        return response()->streamDownload(function () use ($qrCode) {
            echo $qrCode;
        }, str_replace('.pdf', '.png', $fileName), [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . str_replace('.pdf', '.png', $fileName) . '"'
        ]);
    }
    
    /**
     * Generate enhanced HTML for PDF conversion
     */
    private function generatePdfHtml($qrCodeData, $imageType = 'png')
    {
        // Clean and format the content properly
        $title = !empty($this->companyName) ? htmlspecialchars(trim($this->companyName)) . ' - QR Code' : 'QR Code';
        
        // Format content info with proper escaping
        $contentValue = htmlspecialchars(Str::limit(trim($this->qrValue), 100));
        $contentType = htmlspecialchars($this->contentType);
        $errorCorrection = htmlspecialchars($this->errorCorrection);
        
        // Generate image tag based on type
        if ($imageType === 'svg') {
            $imageTag = base64_decode($qrCodeData);
        } else {
            $imageTag = '<img src="data:image/png;base64,' . $qrCodeData . '" alt="QR Code" style="max-width: 400px; height: auto; display: block; margin: 0 auto;">';
        }
        
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . $title . '</title>
            <style>
                @page {
                    margin: 20mm;
                    size: A4 portrait;
                }
                
                body {
                    font-family: Arial, Helvetica, sans-serif;
                    text-align: center;
                    margin: 0;
                    padding: 0;
                    background: white;
                    color: #333;
                    line-height: 1.6;
                }
                
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                
                .header {
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid ' . htmlspecialchars($this->borderColor) . ';
                }
                
                h1 {
                    color: ' . htmlspecialchars($this->borderColor) . ';
                    margin: 0 0 10px 0;
                    font-size: 28px;
                    font-weight: bold;
                }
                
                .subtitle {
                    color: #666;
                    font-size: 14px;
                    margin: 0;
                }
                
                .qr-section {
                    margin: 40px 0;
                    padding: 30px;
                    background: #f8f9fa;
                    border-radius: 10px;
                    border: 1px solid #e9ecef;
                }
                
                .qr-code {
                    margin: 30px 0;
                    text-align: center;
                }
                
                .info-table {
                    background: white;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 30px 0;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid #eee;
                }
                
                .info-row:last-child {
                    border-bottom: none;
                }
                
                .info-label {
                    font-weight: bold;
                    color: #555;
                    width: 40%;
                    text-align: left;
                }
                
                .info-value {
                    color: #333;
                    width: 60%;
                    text-align: left;
                    word-break: break-word;
                }
                
                .footer {
                    margin-top: 50px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #666;
                    font-size: 12px;
                }
                
                .generated-info {
                    background: #e8f5e8;
                    border: 1px solid #c3e6c3;
                    border-radius: 5px;
                    padding: 15px;
                    margin: 20px 0;
                    color: #2d5016;
                }
                
                .usage-tips {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 5px;
                    padding: 15px;
                    margin: 20px 0;
                    text-align: left;
                    color: #856404;
                }
                
                .tips-title {
                    font-weight: bold;
                    margin-bottom: 10px;
                    color: #856404;
                }
                
                .tips-list {
                    font-size: 11px;
                    line-height: 1.4;
                    margin: 0;
                    padding-left: 15px;
                }
                
                .tips-list li {
                    margin: 5px 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . $title . '</h1>
                    <p class="subtitle">Professional QR Code Document</p>
                </div>
                
                <div class="qr-section">
                    <div class="qr-code">
                        ' . $imageTag . '
                    </div>
                </div>
                
                <div class="info-table">
                    <div class="info-row">
                        <span class="info-label">Content Type:</span>
                        <span class="info-value">' . $contentType . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Content:</span>
                        <span class="info-value">' . $contentValue . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Error Correction:</span>
                        <span class="info-value">' . $errorCorrection . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Layout Style:</span>
                        <span class="info-value">' . ucfirst(htmlspecialchars($this->downloadLayout)) . '</span>
                    </div>
                    ' . (!empty($this->companyName) ? '
                    <div class="info-row">
                        <span class="info-label">Company:</span>
                        <span class="info-value">' . htmlspecialchars($this->companyName) . '</span>
                    </div>
                    ' : '') . '
                </div>
                
                <div class="generated-info">
                    <strong>Generated:</strong> ' . now()->format('F j, Y \a\t g:i A T') . '<br>
                    <strong>Generator:</strong> Cool QR Generator v2.0
                </div>
                
                <div class="usage-tips">
                    <div class="tips-title">📱 Usage Tips:</div>
                    <ul class="tips-list">
                        <li>Test the QR code with multiple devices before mass distribution</li>
                        <li>Ensure good lighting when scanning printed QR codes</li>
                        <li>For best results, maintain original size when printing</li>
                        <li>If the QR code contains a URL, verify the link is accessible</li>
                        <li>Keep a backup copy of this PDF for your records</li>
                    </ul>
                </div>
                
                <div class="footer">
                    <p>This QR code was generated using Cool QR Generator.</p>
                    <p>For support or to create more QR codes, visit our website.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Download EPS QR code
     */
    private function downloadEpsQrCode($content, $rendererStyle)
    {
        $renderer = new ImageRenderer($rendererStyle, new EpsImageBackEnd());
        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($content);
        
        $fileName = $this->getFileName('eps');
        
        return response()->streamDownload(function () use ($qrCode) {
            echo $qrCode;
        }, $fileName, [
            'Content-Type' => 'application/postscript',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }
    
    /**
     * Apply standard layout to image
     */
    private function applyStandardLayoutImage($qrCode)
    {
        try {
            if (!class_exists('Intervention\\Image\\Facades\\Image')) {
                return $qrCode;
            }

            $img = Image::make($qrCode);
            $qrSize = $img->width();
            
            // Calculate dimensions for standard layout
            $padding = 50;
            $logoSpace = $this->logoTempPath ? 120 : 0;
            $textSpace = !empty($this->companyName) ? 60 : 0;
            
            $canvasWidth = $qrSize + ($padding * 2);
            $canvasHeight = $qrSize + ($padding * 2) + $logoSpace + $textSpace;
            
            // Create canvas
            $canvas = Image::canvas($canvasWidth, $canvasHeight, $this->borderColor);
            
            // Position QR code
            $qrY = $padding + $logoSpace;
            $canvas->insert($img, 'top', 0, $qrY);
            
            // Add logo above QR code
            if ($this->logoTempPath && Storage::disk('public')->exists($this->logoTempPath)) {
                $logoPath = Storage::disk('public')->path($this->logoTempPath);
                $logo = Image::make($logoPath);
                
                $logoSize = 80;
                $logo->resize($logoSize, $logoSize, function ($constraint) {
                    $constraint->aspectRatio();
                });
                
                $logoY = ($logoSpace - $logo->height()) / 2 + $padding;
                $canvas->insert($logo, 'top', 0, $logoY);
            }
            
            // Add company name below QR code
            if (!empty($this->companyName)) {
                $fontSize = max(18, intval($canvasWidth / 20));
                $textY = $qrY + $qrSize + 30;
                
                $fontPath = $this->findSystemFont();
                if ($fontPath) {
                    $canvas->text($this->companyName, $canvasWidth / 2, $textY, function($font) use ($fontSize, $fontPath) {
                        $font->file($fontPath);
                        $font->size($fontSize);
                        $font->color('#FFFFFF');
                        $font->align('center');
                        $font->valign('top');
                    });
                }
            }
            
            return (string) $canvas->encode('png', 95);
            
        } catch (\Exception $e) {
            \Log::warning('Standard layout application failed: ' . $e->getMessage());
            return $qrCode;
        }
    }
    
    /**
     * Apply side-by-side layout to image (enhanced version)
     */
    private function applySideBySideLayoutImage($qrCode)
    {
        try {
            if (!class_exists('Intervention\\Image\\Facades\\Image')) {
                return $qrCode;
            }

            $img = Image::make($qrCode);
            $qrSize = $img->width();
            
            // Enhanced dimensions
            $padding = 60;
            $sideWidth = 350;
            
            $totalWidth = $qrSize + ($sideWidth * 2) + ($padding * 3);
            $totalHeight = $qrSize + ($padding * 2);
            
            // Create canvas with enhanced background
            $canvas = Image::canvas($totalWidth, $totalHeight, $this->borderColor);
            
            // Add subtle texture/gradient effect
            try {
                $overlay = Image::canvas($totalWidth, $totalHeight, 'rgba(255, 255, 255, 0.03)');
                $canvas->insert($overlay, 'top-left');
            } catch (\Exception $e) {
                // Skip if overlay fails
            }
            
            // Position QR code in center
            $qrX = $sideWidth + $padding;
            $canvas->insert($img, 'top-left', $qrX, $padding);
            
            // Enhanced logo on left
            if ($this->logoTempPath && Storage::disk('public')->exists($this->logoTempPath)) {
                $logoPath = Storage::disk('public')->path($this->logoTempPath);
                $logo = Image::make($logoPath);
                
                $logoSize = min($sideWidth - 40, 200);
                $logo->resize($logoSize, $logoSize, function ($constraint) {
                    $constraint->aspectRatio();
                });
                
                // Add subtle background for logo
                $logoBg = Image::canvas($logoSize + 20, $logoSize + 20, 'rgba(255, 255, 255, 0.1)');
                $logoBgX = ($sideWidth - $logoBg->width()) / 2;
                $logoBgY = ($totalHeight - $logoBg->height()) / 2;
                $canvas->insert($logoBg, 'top-left', $logoBgX, $logoBgY);
                
                $logoX = ($sideWidth - $logo->width()) / 2;
                $logoY = ($totalHeight - $logo->height()) / 2;
                $canvas->insert($logo, 'top-left', $logoX, $logoY);
            }
            
            // Enhanced company name on right
            if (!empty($this->companyName)) {
                $fontSize = max(20, intval($totalHeight / 12));
                $textX = $qrX + $qrSize + $padding + ($sideWidth / 2);
                $textY = $totalHeight / 2;
                
                // Add text background for better readability
                $textBg = Image::canvas($sideWidth - 20, 60, 'rgba(255, 255, 255, 0.1)');
                $textBgX = $qrX + $qrSize + $padding + 10;
                $textBgY = $textY - 30;
                $canvas->insert($textBg, 'top-left', $textBgX, $textBgY);
                
                $fontPath = $this->findSystemFont();
                if ($fontPath) {
                    $canvas->text($this->companyName, $textX, $textY, function($font) use ($fontSize, $fontPath) {
                        $font->file($fontPath);
                        $font->size($fontSize);
                        $font->color('#FFFFFF');
                        $font->align('center');
                        $font->valign('middle');
                        $font->angle(0);
                    });
                }
            }
            
            return (string) $canvas->encode('png', 95);
            
        } catch (\Exception $e) {
            \Log::warning('Side-by-side layout application failed: ' . $e->getMessage());
            return $qrCode;
        }
    }
    
    /**
     * Generate enhanced filename based on content and settings
     */
    private function getFileName($extension)
    {
        $baseName = 'qrcode';
        
        // Add company name if provided
        if (!empty($this->companyName)) {
            $cleanName = Str::slug(Str::limit($this->companyName, 20));
            $baseName = $cleanName . '_qrcode';
        }
        
        // Add content type
        $contentHint = strtolower($this->contentType);
        
        // Add layout type
        $layoutHint = $this->downloadLayout;
        
        // Add timestamp for uniqueness
        $timestamp = now()->format('Ymd_His');
        
        return "{$baseName}_{$contentHint}_{$layoutHint}_{$timestamp}.{$extension}";
    }
    
    /**
     * Clean up temporary files
     */
    public function cleanupTempFiles()
    {
        if ($this->logoTempPath && Storage::disk('public')->exists($this->logoTempPath)) {
            try {
                Storage::disk('public')->delete($this->logoTempPath);
                $this->logoTempPath = null;
            } catch (\Exception $e) {
                \Log::warning('Failed to cleanup temp logo file: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Component cleanup when destroyed
     */
    public function dehydrate()
    {
        // Clean up old temp files periodically
        if (rand(1, 100) === 1) { // 1% chance
            $this->cleanupOldTempFiles();
        }
    }
    
    /**
     * Clean up old temporary files (older than 1 hour)
     */
    private function cleanupOldTempFiles()
    {
        try {
            $files = Storage::disk('public')->files('logos');
            $oneHourAgo = now()->subHour();
            
            foreach ($files as $file) {
                $lastModified = Storage::disk('public')->lastModified($file);
                if ($lastModified < $oneHourAgo->timestamp) {
                    Storage::disk('public')->delete($file);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Temp file cleanup failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.qr-code-generator');
    }
}