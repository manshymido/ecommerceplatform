import { MapPin } from 'lucide-react';
import type { Address } from '../api/types';

interface AddressDisplayProps {
  address: Address | null | undefined;
  title?: string;
}

export function AddressDisplay({ address, title = 'Shipping To' }: AddressDisplayProps) {
  if (!address) return null;

  return (
    <div className="card p-5">
      <div className="flex items-center gap-2 mb-3">
        <MapPin className="w-4 h-4 text-accent" />
        <h3 className="font-semibold text-text-primary text-sm">{title}</h3>
      </div>
      <div className="text-sm text-text-secondary space-y-0.5">
        {address.name && <p className="font-medium text-text-primary">{address.name}</p>}
        {address.line1 && <p>{address.line1}</p>}
        {address.line2 && <p>{address.line2}</p>}
        {address.city && (
          <p>
            {address.city}
            {address.state ? `, ${address.state}` : ''}
            {address.postal_code ? ` ${address.postal_code}` : ''}
          </p>
        )}
        {address.country && <p>{address.country}</p>}
      </div>
    </div>
  );
}
