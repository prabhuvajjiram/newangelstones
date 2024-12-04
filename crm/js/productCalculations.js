class ProductCalculations {
    static calculateSquareFeet(length, breadth) {
        return (length * breadth) / 144;
    }

    static calculateCubicFeet(length, breadth, size, quantity, isMarker = false, thickness = 4.00) {
        if (isMarker) {
            return (length * breadth * thickness) / 1728 * quantity;
        }
        return (length * breadth * size) / 1728 * quantity;
    }

    static calculateBasePrice(basePrice, sqft) {
        return basePrice * sqft;
    }

    static calculateMarkup(basePrice, percentage) {
        return basePrice * (percentage / 100);
    }

    static calculateTotalPrice(measurements, product, colorMarkup = 0, monumentMarkup = 0) {
        const {length, breadth, quantity} = measurements;
        const {base_price, type, size, thickness_inches} = product;

        // Calculate square feet
        const sqft = this.calculateSquareFeet(length, breadth);

        // Calculate cubic feet
        const cubicFeet = this.calculateCubicFeet(
            length, 
            breadth, 
            size, 
            quantity, 
            type === 'marker',
            thickness_inches
        );

        // Calculate base price
        const rawBasePrice = this.calculateBasePrice(base_price, sqft);

        // Calculate markups
        const colorMarkupAmount = this.calculateMarkup(rawBasePrice, colorMarkup);
        const monumentMarkupAmount = this.calculateMarkup(rawBasePrice, monumentMarkup);

        // Calculate final price
        const finalPrice = (rawBasePrice + colorMarkupAmount + monumentMarkupAmount) * quantity;

        return {
            sqft: sqft,
            cubicFeet: cubicFeet,
            basePrice: rawBasePrice,
            totalPrice: finalPrice
        };
    }
}
