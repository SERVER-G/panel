import React, { useEffect } from 'react';
import { ServerContext } from '@/state/server';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import tw from 'twin.macro';
import useSWR from 'swr';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import getPlayers from '@/api/server/playermanager/getPlayers';
import Spinner from '@/components/elements/Spinner';
import PlayerList, { Players } from '@/components/server/playermanager/PlayerList';
import OpList from '@/components/server/playermanager/OpList';
import WhiteList from '@/components/server/playermanager/WhiteList';
import Bans from '@/components/server/playermanager/Bans';
import BanIps from '@/components/server/playermanager/BanIps';

export interface PlayerManagerResponse {
    players: Players;
    ops: any[];
    whitelist: any[];
    bans: any[];
    banIps: any[];
}

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const { data, error, mutate } = useSWR<PlayerManagerResponse>([ uuid, '/playermanager' ], uuid => getPlayers(uuid), {
        refreshInterval: 10000,
    });

    useEffect(() => {
        if (!error) {
            clearFlashes('server:playermanager');
        } else {
            clearAndAddHttpError({ key: 'server:playermanager', error });
        }
    }, [ error ]);

    return (
        <ServerContentBlock title={'Minecraft Player Manager'} css={tw`flex flex-wrap`}>
            <div css={tw`w-full`}>
                <FlashMessageRender byKey={'server:playermanager'} css={tw`mb-4`} />
            </div>
            {!data ?
                <div css={tw`w-full`}>
                    <Spinner size={'large'} centered />
                </div>
                :
                <>
                    <div css={tw`w-full`}>
                        <PlayerList players={data.players} onUpdate={() => mutate()} />
                    </div>
                    <div css={tw`w-full lg:w-1/2 pt-4 lg:pr-4`}>
                        <OpList players={data.ops} onUpdate={() => mutate()} />
                    </div>
                    <div css={tw`w-full lg:w-1/2 pt-4`}>
                        <WhiteList players={data.whitelist} onUpdate={() => mutate()} />
                    </div>
                    <div css={tw`w-full lg:w-1/2 pt-4 lg:pr-4`}>
                        <Bans players={data.bans} onUpdate={() => mutate()} />
                    </div>
                    <div css={tw`w-full lg:w-1/2 pt-4`}>
                        <BanIps players={data.banIps} onUpdate={() => mutate()} />
                    </div>
                </>
            }
        </ServerContentBlock>
    );
}
;
