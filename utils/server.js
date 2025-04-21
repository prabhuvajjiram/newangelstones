import express from 'express';
import cors from 'cors';
import { auth } from 'express-oauth2-jwt-bearer';
import { readFile } from 'fs/promises';

const app = express();
const port = 3000;

// Enable CORS
app.use(cors());

// Configure Auth0 authentication middleware
const jwtCheck = auth({
  audience: 'https://shipping-api',
  issuerBaseURL: 'https://your-tenant.auth0.com/',
  tokenSigningAlg: 'RS256'
});

// Make authentication optional based on environment
const authenticateIfRequired = (req, res, next) => {
  if (process.env.NODE_ENV === 'production') {
    return jwtCheck(req, res, next);
  }
  next();
};

// Use conditional authentication for all routes
app.use(authenticateIfRequired);

// Read shipping data
const getShippingData = async () => {
  try {
    const data = await readFile(new URL('./data/shipping.json', import.meta.url));
    return JSON.parse(data);
  } catch (error) {
    console.error('Error reading shipping data:', error);
    return { shipments: [] };
  }
};

// List all shipping IDs
app.get('/list', async (req, res) => {
  try {
    const data = await getShippingData();
    const shippingIds = data.shipments.map(shipment => shipment.shipping_id);
    res.json({
      count: shippingIds.length,
      shipping_ids: shippingIds,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get shipping details by ID
app.get('/shipping', async (req, res) => {
  try {
    const id = req.query.id;
    if (!id) {
      return res.status(400).json({ error: 'Missing shipping ID parameter' });
    }

    const data = await getShippingData();
    const shipment = data.shipments.find(s => s.shipping_id === id);

    if (!shipment) {
      return res.status(404).json({
        error: 'Shipping ID not found',
        shipping_id: id
      });
    }

    res.json({
      ...shipment,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// API info route
app.get('/', (req, res) => {
  res.json({
    name: 'Shipping REST API',
    version: '1.0',
    endpoints: {
      '/list': 'Get all shipping IDs',
      '/shipping?id=X': 'Get details for a specific shipping ID'
    }
  });
});

app.listen(port, () => {
  console.log(`Server running at http://localhost:${port}`);
  console.log(`Authentication is ${process.env.NODE_ENV === 'production' ? 'enabled' : 'disabled'}`);
});