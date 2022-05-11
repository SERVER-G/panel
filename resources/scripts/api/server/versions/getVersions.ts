import http from '@/api/http';
import { VersionsResponse } from '@/components/server/versions/VersionsContainer';

export default async (uuid: string): Promise<VersionsResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/versions`);

    return (data.data || []);
};
