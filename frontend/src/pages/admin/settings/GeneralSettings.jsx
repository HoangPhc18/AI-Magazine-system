import React, { useState } from 'react';
import { Button, Input, TextArea } from '../../../components';

const GeneralSettings = () => {
  const [settings, setSettings] = useState({
    siteName: '',
    siteDescription: '',
    contactEmail: '',
    contactPhone: '',
    address: '',
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    // TODO: Implement settings update
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-900">General Settings</h2>
        <p className="mt-1 text-sm text-gray-500">
          Configure your site's general settings
        </p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <Input
            label="Site Name"
            value={settings.siteName}
            onChange={(e) =>
              setSettings({ ...settings, siteName: e.target.value })
            }
          />
        </div>

        <div>
          <TextArea
            label="Site Description"
            value={settings.siteDescription}
            onChange={(e) =>
              setSettings({ ...settings, siteDescription: e.target.value })
            }
          />
        </div>

        <div>
          <Input
            label="Contact Email"
            type="email"
            value={settings.contactEmail}
            onChange={(e) =>
              setSettings({ ...settings, contactEmail: e.target.value })
            }
          />
        </div>

        <div>
          <Input
            label="Contact Phone"
            value={settings.contactPhone}
            onChange={(e) =>
              setSettings({ ...settings, contactPhone: e.target.value })
            }
          />
        </div>

        <div>
          <TextArea
            label="Address"
            value={settings.address}
            onChange={(e) =>
              setSettings({ ...settings, address: e.target.value })
            }
          />
        </div>

        <div className="flex justify-end">
          <Button type="submit">Save Changes</Button>
        </div>
      </form>
    </div>
  );
};

export default GeneralSettings; 