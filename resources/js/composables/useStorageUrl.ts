/**
 * Composable for generating storage URLs in Vue components
 * Ensures all storage URLs use the correct domain from APP_URL
 * instead of hardcoded IPs or relative paths that might cause SSL issues
 */

export function useStorageUrl() {
    /**
     * Generate a storage URL for a file path
     * @param filePath - The file path relative to storage/app/public
     * @returns Full URL to the storage file
     */
    const getStorageUrl = (filePath: string): string => {
        if (!filePath) return '';
        
        // Remove any leading slashes
        const cleanPath = filePath.replace(/^\/+/, '');
        
        // Remove 'storage/' prefix if present (to avoid double /storage/)
        const pathWithoutStorage = cleanPath.replace(/^storage\//, '');
        
        // Use relative path which will automatically use the current domain
        // This ensures HTTPS and proper domain are used
        return `/storage/${pathWithoutStorage}`;
    };

    /**
     * Generate a storage URL for opening in new window
     * Useful for download/view operations
     * @param filePath - The file path relative to storage/app/public
     * @returns Full URL to the storage file
     */
    const getStorageUrlForDownload = (filePath: string): string => {
        const url = getStorageUrl(filePath);
        
        // If we're on a secure connection, ensure HTTPS
        if (typeof window !== 'undefined' && window.location.protocol === 'https:') {
            // If the URL is relative, it will use current domain automatically
            // If it's absolute with IP, we need to replace it
            if (url.startsWith('http://') || url.startsWith('https://')) {
                // Extract just the path part
                try {
                    const urlObj = new URL(url);
                    return urlObj.pathname + urlObj.search;
                } catch {
                    // If URL parsing fails, return as is
                    return url;
                }
            }
        }
        
        return url;
    };

    return {
        getStorageUrl,
        getStorageUrlForDownload,
    };
}
