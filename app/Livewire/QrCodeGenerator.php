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

class QrCodeGenerator extends Component
{
    use WithFileUploads;

    // Content properties
    public $qrValue = 'https://example.com';
    public $contentType = 'URL';
    
    // Style properties
    public $borderColor = '#10b981'; // Cool green
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
    
    protected $rules = [
        'qrValue' => 'required',
        'contentType' => 'required|in:URL,Text,Email,Phone,SMS,WiFi',
        'borderColor' => 'required',
        'qrStyle' => 'required|in:square,dots,round',
        'errorCorrection' => 'required|in:L,M,Q,H',
        'logo' => 'nullable|image|max:2048', // 2MB Max
        'companyName' => 'nullable|string|max:255',
        'logoPosition' => 'nullable|in:Center,Top Left,Top Right,Bottom Left,Bottom Right',
        'logoSize' => 'integer|min:10|max:40', // Limited to 10-40% of QR code size
        'downloadLayout' => 'required|in:standard,sides',
    ];
    
    public function mount()
    {
        $this->generateQrCode();
    }
    
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['qrValue', 'contentType', 'qrStyle', 'errorCorrection', 'size', 'borderColor', 'companyName', 'logoPosition', 'logoSize'])) {
            $this->generateQrCode();
        }
        
        if ($propertyName === 'logo') {
            $this->processLogo();
            $this->generateQrCode();
        }
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function selectQrStyle($style)
    {
        $this->qrStyle = $style;
        $this->generateQrCode();
    }
    
    /**
     * Process and store the uploaded logo
     */
    public function processLogo()
    {
        if (!$this->logo) {
            $this->logoTempPath = null;
            return;
        }
        
        try {
            // Store the logo temporarily
            $this->logoTempPath = $this->logo->store('logos', 'public');
            
            // Process logo image for better display
            if (class_exists('Intervention\\Image\\Facades\\Image')) {
                $logoPath = Storage::disk('public')->path($this->logoTempPath);
                
                // Create a square logo with transparent background
                $logo = Image::make($logoPath);
                
                // Create a square canvas with transparent background
                $size = max($logo->width(), $logo->height());
                $canvas = Image::canvas($size, $size, 'rgba(255, 255, 255, 0)'); // Transparent background
                
                // Insert the logo in the center
                $canvas->insert($logo, 'center');
                
                // Add a white background circle/square behind the logo for better visibility
                $canvas->insert($this->createLogoBackground($size), 'center');
                $canvas->insert($logo, 'center');
                
                // Save the processed logo
                $canvas->save($logoPath, 100);
            }
        } catch (\Exception $e) {
            // Handle error if needed
            $this->logoTempPath = null;
        }
    }
    
    /**
     * Create a background image for the logo
     * 
     * @param int $size Image size
     * @return \Intervention\Image\Image
     */
    private function createLogoBackground($size)
    {
        // Create white background with slight transparency
        $background = Image::canvas($size * 0.8, $size * 0.8, 'rgba(255, 255, 255, 0.85)');
        
        // Round the corners if needed
        if ($this->qrStyle === 'round' || $this->qrStyle === 'dots') {
            // We need to create a circle mask for the background
            // This simplified implementation just creates a white rounded background
            $background = Image::canvas($size * 0.8, $size * 0.8, 'rgba(255, 255, 255, 0)');
            $background->circle($size * 0.8, $size * 0.4, $size * 0.4, function ($draw) {
                $draw->background('rgba(255, 255, 255, 0.85)');
            });
        }
        
        return $background;
    }
    
    public function generateQrCode()
    {
        // Format content based on content type
        $content = $this->formatContent();
        
        // Set a higher error correction level when a logo is present
        $errorCorrection = $this->logoTempPath ? 'H' : $this->errorCorrection;
        
        // Create a renderer style with appropriate margin
        $margin = 1; // Default margin
        $rendererStyle = new RendererStyle($this->size, $margin);
        
        try {
            // Try to use Imagick backend if available
            if (!extension_loaded('imagick')) {
                throw new \Exception('ImageMagick extension not available');
            }
            
            $renderer = new ImageRenderer($rendererStyle, new ImagickImageBackEnd());
            $writer = new Writer($renderer);
            $qrCode = $writer->writeString($content);
            
            // Apply branding to PNG QR code
            if (class_exists('Intervention\\Image\\Facades\\Image')) {
                $qrCode = $this->applyBrandingToImage($qrCode);
            }
            
            $this->generatedQrCode = base64_encode($qrCode);
        } catch (\Exception $e) {
            // Fallback to SVG if Imagick is not available
            $renderer = new ImageRenderer($rendererStyle, new SvgImageBackEnd());
            $writer = new Writer($renderer);

            if($content){
                $qrCode = $writer->writeString($content);
            
                // For SVG we can modify it directly to add styling
                $svgQrCode = $this->styleSvgQrCode($qrCode);
                
                // Apply branding to SVG
                $svgQrCode = $this->applyBrandingToSvg($svgQrCode);
                
                $this->generatedQrCode = base64_encode($svgQrCode);
    
            }
          
            
        }
    }
    
    /**
     * Apply branding to image-based QR code
     * 
     * @param string $qrCode Binary image data
     * @return string Modified binary image data
     */
    private function applyBrandingToImage($qrCode)
    {
        try {
            // Only proceed if Intervention Image is available
            if (!class_exists('Intervention\\Image\\Facades\\Image')) {
                return $qrCode;
            }

            // Create image instance from binary data
            $img = Image::make($qrCode);
            
            // Add border with selected color
            $borderSize = 20; // Increased border thickness for better visibility
            $width = $img->width() + ($borderSize * 2);
            $height = $img->height() + ($borderSize * 2);
            
            // Create new canvas with border
            $canvas = Image::canvas($width, $height, $this->borderColor);
            
            // Place QR code on the canvas
            $canvas->insert($img, 'center');
            
            // Add logo if provided
            if ($this->logoTempPath) {
                $logoPath = Storage::disk('public')->path($this->logoTempPath);
                if (file_exists($logoPath)) {
                    $logo = Image::make($logoPath);
                    
                    // Resize logo based on the logoSize percentage
                    $logoSize = $img->width() * ($this->logoSize / 100);
                    $logo->resize($logoSize, $logoSize, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    
                    // Position logo based on selected position
                    $position = $this->getLogoPosition($canvas->width(), $canvas->height(), $logo->width(), $logo->height());
                    
                    // Add logo to canvas
                    $canvas->insert($logo, $position['position'], $position['x'], $position['y']);
                }
            }
            
            // Add company name as text if provided
            if (!empty($this->companyName)) {
                $fontSize = intval($width / 20); // Dynamic font size based on QR code size
                $canvas->text($this->companyName, $width / 2, $height - ($borderSize / 2), function($font) use ($fontSize) {
                    $font->file(public_path('fonts/arial.ttf')); // Ensure this font exists
                    $font->size($fontSize);
                    $font->color('#FFFFFF');
                    $font->align('center');
                    $font->valign('bottom');
                });
            }
            
            // Convert back to binary data
            return (string) $canvas->encode('png');
        } catch (\Exception $e) {
            // If anything goes wrong, return the original QR code
            return $qrCode;
        }
    }
    
    /**
     * Calculate logo position coordinates
     * 
     * @param int $canvasWidth Canvas width
     * @param int $canvasHeight Canvas height
     * @param int $logoWidth Logo width
     * @param int $logoHeight Logo height
     * @return array Position data
     */
    private function getLogoPosition($canvasWidth, $canvasHeight, $logoWidth, $logoHeight)
    {
        $padding = 20; // Padding from edges
        
        switch ($this->logoPosition) {
            case 'Top Left':
                return [
                    'position' => 'top-left',
                    'x' => $padding,
                    'y' => $padding
                ];
            case 'Top Right':
                return [
                    'position' => 'top-right',
                    'x' => $padding,
                    'y' => $padding
                ];
            case 'Bottom Left':
                return [
                    'position' => 'bottom-left',
                    'x' => $padding,
                    'y' => $padding
                ];
            case 'Bottom Right':
                return [
                    'position' => 'bottom-right',
                    'x' => $padding,
                    'y' => $padding
                ];
            default: // Center
                return [
                    'position' => 'center',
                    'x' => 0,
                    'y' => 0
                ];
        }
    }
    
    /**
     * Apply branding to SVG QR code
     * 
     * @param string $svgQrCode The SVG QR code string
     * @return string The branded SVG QR code
     */
    private function applyBrandingToSvg($svgQrCode)
    {
        // Parse the SVG to get its dimensions
        preg_match('/<svg[^>]*width="([^"]*)"[^>]*height="([^"]*)"/', $svgQrCode, $matches);
        
        if (count($matches) < 3) {
            return $svgQrCode;
        }
        
        $width = floatval($matches[1]);
        $height = floatval($matches[2]);
        
        // Add a border rect with the selected color
        $borderSize = 20; // Increased border size for better visibility
        $newWidth = $width + ($borderSize * 2);
        $newHeight = $height + ($borderSize * 2);
        
        // Create new SVG with border
        $svgPrefix = '<?xml version="1.0" encoding="UTF-8"?>';
        $svgOpen = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' . $newWidth . '" height="' . $newHeight . '" viewBox="0 0 ' . $newWidth . ' ' . $newHeight . '">';
        
        // Add border rectangle
        $border = '<rect x="0" y="0" width="' . $newWidth . '" height="' . $newHeight . '" fill="' . $this->borderColor . '"/>';
        
        // Extract SVG content (everything between <svg> and </svg>)
        preg_match('/<svg[^>]*>(.*)<\/svg>/s', $svgQrCode, $contentMatches);
        $svgContent = isset($contentMatches[1]) ? $contentMatches[1] : '';
        
        // Wrap the original QR code in a group and translate it to account for the border
        $qrGroup = '<g transform="translate(' . $borderSize . ',' . $borderSize . ')">' . $svgContent . '</g>';
        
        // Add logo if provided
        $logoSvg = '';
        if ($this->logoTempPath) {
            try {
                // Calculate logo size based on the percentage
                $logoSize = $width * ($this->logoSize / 100);
                
                // Calculate position based on selected position
                $logoX = 0;
                $logoY = 0;
                
                $padding = 20; // Padding from edges
                switch ($this->logoPosition) {
                    case 'Top Left':
                        $logoX = $borderSize + $padding;
                        $logoY = $borderSize + $padding;
                        break;
                    case 'Top Right':
                        $logoX = $newWidth - $logoSize - $borderSize - $padding;
                        $logoY = $borderSize + $padding;
                        break;
                    case 'Bottom Left':
                        $logoX = $borderSize + $padding;
                        $logoY = $newHeight - $logoSize - $borderSize - $padding;
                        break;
                    case 'Bottom Right':
                        $logoX = $newWidth - $logoSize - $borderSize - $padding;
                        $logoY = $newHeight - $logoSize - $borderSize - $padding;
                        break;
                    default: // Center
                        $logoX = ($newWidth - $logoSize) / 2;
                        $logoY = ($newHeight - $logoSize) / 2;
                }
                
                // Create a white background for the logo for better visibility
                $bgRadius = $this->qrStyle === 'round' || $this->qrStyle === 'dots' ? '50%' : '15%';
                $logoBackground = '<rect x="' . ($logoX - $logoSize * 0.1) . '" y="' . ($logoY - $logoSize * 0.1) . '" 
                                  width="' . ($logoSize * 1.2) . '" height="' . ($logoSize * 1.2) . '" 
                                  fill="white" opacity="0.9" rx="' . $bgRadius . '" ry="' . $bgRadius . '"/>';
                
                // For SVG, we can try to load and embed the logo as a base64 image
                $logoPath = Storage::disk('public')->path($this->logoTempPath);
                if (file_exists($logoPath)) {
                    $logoMime = mime_content_type($logoPath);
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $logoTag = '<image x="' . $logoX . '" y="' . $logoY . '" width="' . $logoSize . '" height="' . $logoSize . '" 
                              xlink:href="data:' . $logoMime . ';base64,' . $logoData . '"/>';
                    
                    $logoSvg = $logoBackground . $logoTag;
                } else {
                    // Fallback to a placeholder if file doesn't exist
                    $logoSvg = '<rect x="' . $logoX . '" y="' . $logoY . '" width="' . $logoSize . '" height="' . $logoSize . '" 
                               fill="white" opacity="0.8" rx="5" ry="5"/>';
                }
            } catch (\Exception $e) {
                // Fallback to a simple placeholder if there's an error
                $logoSize = $width * 0.2;
                $logoX = ($newWidth - $logoSize) / 2;
                $logoY = ($newHeight - $logoSize) / 2;
                
                $logoSvg = '<rect x="' . $logoX . '" y="' . $logoY . '" width="' . $logoSize . '" height="' . $logoSize . '" 
                           fill="white" opacity="0.8" rx="5" ry="5"/>';
            }
        }
        
        // Add company name text if provided
        $companyText = '';
        if (!empty($this->companyName)) {
            $fontSize = $newWidth / 25; // Dynamic font size based on QR code size
            $companyText = '<text x="' . ($newWidth / 2) . '" y="' . ($newHeight - $borderSize / 2) . '" 
                          text-anchor="middle" font-family="Arial" font-size="' . $fontSize . '" fill="white">' 
                          . htmlspecialchars($this->companyName) . '</text>';
        }
        
        // Make sure to add the svg namespace for xlink
        $svgOpen = str_replace('<svg ', '<svg xmlns:xlink="http://www.w3.org/1999/xlink" ', $svgOpen);
        
        // Combine all SVG elements
        $newSvg = $svgPrefix . $svgOpen . $border . $qrGroup . $logoSvg . $companyText . '</svg>';
        
        return $newSvg;
    }
    
    /**
     * Apply styling to SVG QR Code
     * 
     * @param string $svgQrCode The SVG QR code string
     * @return string The styled SVG QR code
     */
    private function styleSvgQrCode($svgQrCode)
    {
        // Apply styling based on the selected QR style
        switch ($this->qrStyle) {
            case 'dots':
                // Replace all rectangles with circles
                $svgQrCode = preg_replace('/<rect([^>]*)>/i', '<rect$1 rx="50%" ry="50%">', $svgQrCode);
                break;
                
            case 'round':
                // Add rounded corners to all rectangles
                $svgQrCode = preg_replace('/<rect([^>]*)>/i', '<rect$1 rx="30%" ry="30%">', $svgQrCode);
                break;
                
            // 'square' is the default, no changes needed
        }
        
        return $svgQrCode;
    }
    
    private function formatContent()
    {
        switch ($this->contentType) {
            case 'Email':
                return "mailto:{$this->qrValue}";
                
            case 'Phone':
                // Format for adding to contacts rather than just calling
                $phone = preg_replace('/[^0-9+]/', '', $this->qrValue); // Clean the phone number
                return "tel:{$phone}";
                
            case 'SMS':
                $phone = preg_replace('/[^0-9+]/', '', $this->qrValue);
                return "sms:{$phone}";
                
            case 'WiFi':
                // Parse WiFi string format: SSID,password,WPA
                $wifiParts = explode(',', $this->qrValue);
                if (count($wifiParts) >= 2) {
                    $ssid = trim($wifiParts[0]);
                    $password = trim($wifiParts[1]);
                    $encryption = count($wifiParts) > 2 ? trim($wifiParts[2]) : 'WPA';
                    return "WIFI:S:{$ssid};P:{$password};T:{$encryption};;";
                }
                return "WIFI:Invalid Format;;";
                
            default:
                return $this->qrValue;
        }
    }
    
    /**
     * Download QR code with the requested layout
     * 
     * @param string $format File format (png, svg, eps)
     * @return \Illuminate\Http\Response
     */
    public function downloadQrCode($format = 'png')
    {
        // Format content based on content type
        $content = $this->formatContent();
        
        // Set a higher error correction level when a logo is present
        $errorCorrection = $this->logoTempPath ? 'H' : $this->errorCorrection;
        
        // Create a renderer style (larger size for download)
        $rendererStyle = new RendererStyle(800, 1);
        
        // Determine the output format and setup appropriate renderer
        switch ($format) {
            case 'svg':
                $renderer = new ImageRenderer($rendererStyle, new SvgImageBackEnd());
                $contentType = 'image/svg+xml';
                $fileName = $this->getFileName('svg');
                
                // Generate base QR code
                $writer = new Writer($renderer);
                $qrCode = $writer->writeString($content);
                
                // Apply styling
                $qrCode = $this->styleSvgQrCode($qrCode);
                
                // Apply layout
                $qrCode = $this->applySideBySideLayoutSvg($qrCode);
                break;
                
            case 'eps':
                $renderer = new ImageRenderer($rendererStyle, new EpsImageBackEnd());
                $contentType = 'application/postscript';
                $fileName = $this->getFileName('eps');
                
                // EPS format doesn't support our custom layout properly
                // Generate regular QR code (no branding for EPS)
                $writer = new Writer($renderer);
                $qrCode = $writer->writeString($content);
                break;
                
            default: // png
                try {
                    if (!extension_loaded('imagick')) {
                        throw new \Exception('ImageMagick extension is not available.');
                    }
                    $renderer = new ImageRenderer($rendererStyle, new ImagickImageBackEnd());
                    $contentType = 'image/png';
                    $fileName = $this->getFileName('png');
                    
                    // Generate base QR code
                    $writer = new Writer($renderer);
                    $qrCode = $writer->writeString($content);
                    
                    // Apply side-by-side layout with logo and text
                    if (class_exists('Intervention\\Image\\Facades\\Image')) {
                        $qrCode = $this->applySideBySideLayoutImage($qrCode);
                    }
                } catch (\Exception $e) {
                    // Fallback to SVG if Imagick is not available
                    $renderer = new ImageRenderer($rendererStyle, new SvgImageBackEnd());
                    $contentType = 'image/svg+xml';
                    $fileName = $this->getFileName('svg');
                    
                    // Generate QR code
                    $writer = new Writer($renderer);
                    $qrCode = $writer->writeString($content);
                    
                    // Apply styling 
                    $qrCode = $this->styleSvgQrCode($qrCode);
                    
                    // Apply side-by-side layout
                    $qrCode = $this->applySideBySideLayoutSvg($qrCode);
                }
        }
        
        // Return file for download
        return response()->streamDownload(function () use ($qrCode) {
            echo $qrCode;
        }, $fileName, ['Content-Type' => $contentType]);
    }
    
    /**
     * Apply side by side layout for image-based QR code
     * 
     * @param string $qrCode Binary image data
     * @return string Modified binary image data
     */
    private function applySideBySideLayoutImage($qrCode)
    {
        try {
            // Only proceed if Intervention Image is available
            if (!class_exists('Intervention\\Image\\Facades\\Image')) {
                return $qrCode;
            }

            // Create image instance from binary data
            $img = Image::make($qrCode);
            
            // Define dimensions for the new layout
            $qrSize = $img->width();
            $padding = 40;
            $sideWidth = 300; // Width for side panels
            
            // Calculate total dimensions
            $totalWidth = $qrSize + ($sideWidth * 2) + ($padding * 2);
            $totalHeight = $qrSize + ($padding * 2);
            
            // Create a new canvas with the background color
            $canvas = Image::canvas($totalWidth, $totalHeight, $this->borderColor);
            
            // Place QR code in the center
            $canvas->insert($img, 'center');
            
            // Add logo on the left side if provided
            if ($this->logoTempPath) {
                $logoPath = Storage::disk('public')->path($this->logoTempPath);
                if (file_exists($logoPath)) {
                    $logo = Image::make($logoPath);
                    
                    // Resize logo to fit the side panel
                    $logoSize = $sideWidth - $padding;
                    $logo->resize($logoSize, $logoSize, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    
                    // Position logo on the left
                    $logoX = $sideWidth / 2;
                    $logoY = $totalHeight / 2;
                    
                    // Create a white circular background for the logo
                    $bgSize = $logo->width() * 1.2;
                    $bgCanvas = Image::canvas($bgSize, $bgSize, 'rgba(255, 255, 255, 0.9)');
                    
                    // Make it circular if the QR style is dots or round
                    if ($this->qrStyle === 'round' || $this->qrStyle === 'dots') {
                        $bgCanvas->circle($bgSize, $bgSize/2, $bgSize/2, function ($draw) {
                            $draw->background('rgba(255, 255, 255, 0.9)');
                        });
                    }
                    
                    // Add the background and logo to the main canvas
                    $canvas->insert($bgCanvas, 'left', $padding, 0);
                    $canvas->insert($logo, 'left', $padding + ($bgSize - $logo->width())/2, 0);
                }
            }
            
            // Add company name on the right side if provided
            if (!empty($this->companyName)) {
                $fontSize = intval($totalHeight / 15); // Dynamic font size
                
                // For better text display, we'll create a text image
                $textCanvas = Image::canvas($sideWidth, $totalHeight, 'rgba(0, 0, 0, 0)');
                $textCanvas->text($this->companyName, $sideWidth/2, $totalHeight/2, function($font) use ($fontSize) {
                    $font->file(public_path('fonts/arial.ttf')); // Ensure this font exists
                    $font->size($fontSize);
                    $font->color('#FFFFFF');
                    $font->align('center');
                    $font->valign('middle');
                });
                
                // Insert the text on the right side
                $canvas->insert($textCanvas, 'right', $padding, 0);
            }
            
            // Convert back to binary data
            return (string) $canvas->encode('png');
        } catch (\Exception $e) {
            // If anything goes wrong, return the original QR code
            return $qrCode;
        }
    }
    
    /**
     * Apply side by side layout for SVG QR code
     * 
     * @param string $svgQrCode The SVG QR code string
     * @return string The modified SVG QR code
     */
    private function applySideBySideLayoutSvg($svgQrCode)
    {
        // Parse the SVG to get its dimensions
        preg_match('/<svg[^>]*width="([^"]*)"[^>]*height="([^"]*)"/', $svgQrCode, $matches);
        
        if (count($matches) < 3) {
            return $svgQrCode;
        }
        
        $qrSize = floatval($matches[1]);
        
        // Define dimensions for the new layout
        $padding = 40;
        $sideWidth = 300; // Width for side panels
        
        // Calculate total dimensions
        $totalWidth = $qrSize + ($sideWidth * 2) + ($padding * 2);
        $totalHeight = $qrSize + ($padding * 2);
        
        // Create new SVG with border
        $svgPrefix = '<?xml version="1.0" encoding="UTF-8"?>';
        $svgOpen = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="' . $totalWidth . '" height="' . $totalHeight . '" viewBox="0 0 ' . $totalWidth . ' ' . $totalHeight . '">';
        
        // Add background rectangle
        $background = '<rect x="0" y="0" width="' . $totalWidth . '" height="' . $totalHeight . '" fill="' . $this->borderColor . '"/>';
        
        // Extract SVG content (everything between <svg> and </svg>)
        preg_match('/<svg[^>]*>(.*)<\/svg>/s', $svgQrCode, $contentMatches);
        $svgContent = isset($contentMatches[1]) ? $contentMatches[1] : '';
        
        // Position the QR code in the center
        $qrX = $sideWidth + $padding;
        $qrY = $padding;
        $qrGroup = '<g transform="translate(' . $qrX . ',' . $qrY . ')">' . $svgContent . '</g>';
        
        // Add logo on the left side if provided
        $logoSvg = '';
        if ($this->logoTempPath) {
            try {
                // Calculate logo size to fit the side panel
                $logoSize = $sideWidth - ($padding * 2);
                
                // Position for the logo (center of left panel)
                $logoX = $sideWidth / 2 - $logoSize / 2;
                $logoY = $totalHeight / 2 - $logoSize / 2;
                
                // Create a white background for the logo
                $bgSize = $logoSize * 1.2;
                $bgX = $logoX - ($bgSize - $logoSize) / 2;
                $bgY = $logoY - ($bgSize - $logoSize) / 2;
                
                // For rounded QR styles, use a circular background
                $bgRadius = $this->qrStyle === 'round' || $this->qrStyle === 'dots' ? '50%' : '15%';
                $logoBackground = '<rect x="' . $bgX . '" y="' . $bgY . '" width="' . $bgSize . '" height="' . $bgSize . '" 
                                  fill="white" opacity="0.9" rx="' . $bgRadius . '" ry="' . $bgRadius . '"/>';
                
                // For SVG, we can try to load and embed the logo as a base64 image
                $logoPath = Storage::disk('public')->path($this->logoTempPath);
                if (file_exists($logoPath)) {
                    $logoMime = mime_content_type($logoPath);
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $logoTag = '<image x="' . $logoX . '" y="' . $logoY . '" width="' . $logoSize . '" height="' . $logoSize . '" 
                              xlink:href="data:' . $logoMime . ';base64,' . $logoData . '"/>';
                    
                    $logoSvg = $logoBackground . $logoTag;
                }
            } catch (\Exception $e) {
                // If we can't load the logo, we'll just skip it
            }
        }
        
        // Add company name on the right side if provided
        $companyText = '';
        if (!empty($this->companyName)) {
            $fontSize = $totalHeight / 15; // Dynamic font size
            $textX = $qrSize + $sideWidth + $padding * 2;
            $textY = $totalHeight / 2;
            
            // Add the company name text
            $companyText = '<text x="' . $textX . '" y="' . $textY . '" 
                          text-anchor="middle" font-family="Arial" font-size="' . $fontSize . '" fill="white"
                          dominant-baseline="middle">' 
                          . htmlspecialchars($this->companyName) . '</text>';
        }
        
        // Combine all SVG elements
        $newSvg = $svgPrefix . $svgOpen . $background . $qrGroup . $logoSvg . $companyText . '</svg>';
        
        return $newSvg;
    }
    
    /**
     * Generate a filename based on content type and company name
     * 
     * @param string $extension File extension (png, svg, eps)
     * @return string The generated filename
     */
    private function getFileName($extension)
    {
        $baseName = 'qrcode';
        
        // Add company name to filename if provided
        if (!empty($this->companyName)) {
            $cleanCompanyName = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $this->companyName));
            $baseName = strtolower($cleanCompanyName) . '_qrcode';
        }
        
        // Add content type hint
        $contentTypeHint = strtolower($this->contentType);
        
        return "{$baseName}_{$contentTypeHint}.{$extension}";
    }
    
    public function render()
    {
        return view('livewire.qr-code-generator');
    }

}