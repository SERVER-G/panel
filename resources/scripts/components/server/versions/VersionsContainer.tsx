import React, { useEffect } from 'react';
import { ServerContext } from '@/state/server';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import tw from 'twin.macro';
import useSWR from 'swr';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Spinner from '@/components/elements/Spinner';
import getVersions from '@/api/server/versions/getVersions';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { faPuzzlePiece } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import SwitchButton from '@/components/server/versions/SwitchButton';

export interface VersionsResponse {
    versions: any[];
    currentVersion: any;
}

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const { data, error, mutate } = useSWR<VersionsResponse>([ uuid, '/versions' ], (uuid) => getVersions(uuid), {
        revalidateOnFocus: false,
    });

    useEffect(() => {
        if (!error) {
            clearFlashes('server:versions');
        } else {
            clearAndAddHttpError({ key: 'server:versions', error });
        }
    }, [ error ]);

    return (
        <ServerContentBlock title={'Version Changer'} css={tw`flex flex-wrap`}>
            <div css={tw`w-full`}>
                <FlashMessageRender byKey={'server:versions'} css={tw`mb-4`} />
            </div>
            {!data ?
                <div css={tw`w-full`}>
                    <Spinner size={'large'} centered />
                </div>
                :
                <>
                    {data.versions.length < 1 ?
                        <div css={tw`w-full`}>
                            <p css={tw`text-center text-sm text-neutral-400 pt-4 pb-4`}>
                                There are no versions for this server.
                            </p>
                        </div>
                        :
                        (
                            <>
                                {data.versions.map((item, key) => (
                                    <div css={tw`w-full md:w-6/12 md:pl-2 md:pr-2 pt-4`} key={key}>
                                        <TitledGreyBox title={item.name}>
                                            <div css={tw`px-1 py-2`}>
                                                {data.versions[key].versions.map((version: any, versionKey: any) => (
                                                    <GreyRowBox key={`version_${versionKey}`} css={[ tw`bg-neutral-600 flex items-center`, versionKey > 0 && tw`mt-2` ]}>
                                                        <FontAwesomeIcon icon={faPuzzlePiece} css={tw`text-neutral-300`} />
                                                        <div css={tw`ml-4 flex-1 overflow-hidden`}>
                                                            <p css={tw`text-sm break-words`}>{version.name}</p>
                                                        </div>
                                                        <SwitchButton group={item.id} method={version.method} versionKey={version.current_check} currentVersion={data.currentVersion} installed={() => mutate()}></SwitchButton>
                                                    </GreyRowBox>
                                                ))}
                                            </div>
                                        </TitledGreyBox>
                                    </div>
                                ))}
                            </>
                        )
                    }
                </>
            }
        </ServerContentBlock>
    );
};
