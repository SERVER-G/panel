import React from 'react';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import styled from 'styled-components/macro';
import AskModal from '@/components/server/playermanager/AskModal';

const Code = styled.code`${tw`font-mono py-1 px-2 bg-neutral-900 rounded text-sm inline-block`}`;
const Label = styled.label`${tw`uppercase text-xs mt-1 text-neutral-400 block px-1 select-none transition-colors duration-150`}`;

interface Props {
    players: any[];
    onUpdate: () => void;
}

export default ({ players, onUpdate }: Props) => {
    return (
        <>
            <div css={tw`rounded shadow-md bg-neutral-700`}>
                <div css={tw`bg-neutral-900 rounded-t p-3 border-b border-black`}>
                    OP List
                </div>
            </div>
            {players.length < 1 ?
                <p css={tw`text-center text-sm text-neutral-400 pt-4 pb-4`}>
                    There are no players on the list.
                </p>
                :
                <>
                    {players.map((item, key) => (
                        <GreyRowBox $hoverable={false} css={tw`flex-wrap md:flex-nowrap mt-2`} key={key}>
                            <div css={tw`flex items-center w-full md:w-auto`}>
                                <div css={tw`pl-4 pr-6 text-neutral-400`}>
                                    <img src={`//crafatar.com/avatars/${item.uuid}`} alt={'Player Skin'} width={48} />
                                </div>
                            </div>
                            <div css={tw`mt-4 w-full md:mt-0 md:w-80 mr-4`}>
                                <Code>{item.name}</Code>
                                <Label>Username</Label>
                            </div>
                            <div css={tw`w-full md:flex-none md:w-4/12 md:text-center mt-4 md:mt-0 ml-4 flex items-center justify-end`}>
                                <AskModal title={'DEOP Player'} message={`Are you sure that you want to deop <b>${item.name}</b>?`} buttonText={'DEOP'} command={`deop ${item.name}`} onPerformed={() => onUpdate()} />
                            </div>
                        </GreyRowBox>
                    ))}
                </>
            }
        </>
    );
}
;
