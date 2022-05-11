import React, { useEffect } from 'react'

//! States
import { ServerContext } from '@/state/server';

//! Components
import TitledGreyBox from '@/components/elements/TitledGreyBox';

//! Icons
import { faCircle, faUsers, faMap, faInfoCircle, faServer, faCodeBranch, faBug, faFire, faStopwatch } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

//! Plugins
import useFlash from '@/plugins/useFlash';

//! API
import { PlayersResponse } from './PlayersContainer';
import getPlayers from "@/api/server/getPlayers";

//! Vendors
import tw from "twin.macro";
import useSWR from "swr";


const ServerInformation = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error } = useSWR<PlayersResponse>([uuid, '/players'], key => getPlayers(key), {
        revalidateOnFocus: true,
        errorRetryCount: 7,
        refreshInterval: 60000,
    });

    useEffect(() => {
        if (!error) {
            clearFlashes('players');
        } else {
            clearAndAddHttpError({ key: 'players', error });
        }
    }, [error]);

    return (
        <TitledGreyBox css={tw`break-words mt-4`} title={"Server Information"} icon={faServer}>
            {!data ? (
                <>
                    <p css={tw`text-xs mt-2`}>
                        <FontAwesomeIcon icon={faInfoCircle} fixedWidth css={tw`mr-1`} />&nbsp;
                        Unknown
                    </p>
                    <p css={tw`text-xs mt-2`}>
                        <FontAwesomeIcon icon={faMap} fixedWidth css={tw`mr-1`} />&nbsp;
                        Unknown
                    </p>
                    <p css={tw`text-xs mt-2`}>
                        <FontAwesomeIcon icon={faUsers} fixedWidth css={tw`mr-1`} />&nbsp;
                        0 <span css={tw`text-neutral-500`}>/ 0</span>
                    </p>
                    <p css={tw`text-xs mt-2`}>
                        <FontAwesomeIcon icon={faFire} fixedWidth css={tw`mr-1`} />&nbsp;
                        0
                    </p>
                    <p css={tw`text-xs mt-2`}>
                        <FontAwesomeIcon icon={faBug} fixedWidth css={tw`mr-1`} />&nbsp;
                        0
                    </p>
                    <p css={tw`text-xs mt-2`}>
                        <FontAwesomeIcon icon={faStopwatch} fixedWidth css={tw`mr-1`} />&nbsp;
                        0
                    </p>
                    <p css={tw`text-xs mt-2`}>
                        <FontAwesomeIcon icon={faCodeBranch} fixedWidth css={tw`mr-1`} />&nbsp;
                        Unknown
                    </p>
                </>
            ) : (
                <>
                    {!data.error ? (
                        <>
                            {data?.info.hostname && (
                                <p css={tw`text-xs mt-2`}>
                                    <FontAwesomeIcon icon={faInfoCircle} fixedWidth css={tw`mr-1`} />&nbsp;
                                    {data.info.hostname}
                                </p>
                            )}

                            {data?.info.map && (
                                <p css={tw`text-xs mt-2`}>
                                    <FontAwesomeIcon icon={faMap} fixedWidth css={tw`mr-1`} />&nbsp;
                                    {data.info.map}
                                </p>
                            )}

                            <p css={tw`text-xs mt-2`}>
                                <FontAwesomeIcon icon={faUsers} fixedWidth css={tw`mr-1`} />&nbsp;
                                {data?.online_players} &nbsp;
                                <span css={tw`text-neutral-500`}>
                                    / {data?.max_players} &nbsp;
                                    {data?.info.queued != undefined && (
                                        <>({data?.info.joining} Joining, {data?.info.queued} Queue)</>
                                    )}
                                </span>
                            </p>

                            {data?.info.framerate && (
                                <p css={tw`text-xs mt-2`}>
                                    <FontAwesomeIcon icon={faFire} fixedWidth css={tw`mr-1`} />&nbsp;
                                    {data.info.framerate}
                                </p>
                            )}

                            {data?.info.entities && (
                                <p css={tw`text-xs mt-2`}>
                                    <FontAwesomeIcon icon={faBug} fixedWidth css={tw`mr-1`} />&nbsp;
                                    {data.info.entities}
                                </p>
                            )}

                            {data?.info.uptime && (
                                <p css={tw`text-xs mt-2`}>
                                    <FontAwesomeIcon icon={faStopwatch} fixedWidth css={tw`mr-1`} />&nbsp;
                                    {new Date(data.info.uptime * 1000).toISOString().substr(11, 8)}
                                </p>
                            )}

                            {data?.info.version && (
                                <p css={tw`text-xs mt-2`}>
                                    <FontAwesomeIcon icon={faCodeBranch} fixedWidth css={tw`mr-1`} />&nbsp;
                                    {data.info.version}
                                </p>
                            )}
                        </>
                    ) : (
                        <p css={tw`text-xs mt-2`}>
                            <FontAwesomeIcon icon={faCircle} fixedWidth css={tw`mr-1 text-red-500`} />&nbsp;
                            {data?.error}
                        </p>
                    )}
                </>
            )}
        </TitledGreyBox>
    )
}

export default ServerInformation;